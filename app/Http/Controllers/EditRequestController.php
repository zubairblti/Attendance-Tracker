<?php

namespace App\Http\Controllers;

use App\Models\{ApprovalHistory,EditRequest};
use App\Services\{AttendanceCorrectionApplier,RequestNotifier};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EditRequestController extends Controller
{
    public function mine(Request $request){$requests=$request->user()->editRequests()->latest()->get();return view('edit_requests.mine',compact('requests'));}
    public function index(Request $request){$this->authorize('viewAny',EditRequest::class);$user=$request->user();$query=EditRequest::with(['user','timeClock','histories.actor'])->whereHas('user',fn($employee)=>$employee->where('manager_id',$user->id)->orWhereIn('department_id',$user->hodDepartments()->pluck('departments.id')));$requests=$query->latest()->paginate(20);return view('edit_requests.index',compact('requests'));}
    public function store(Request $request,RequestNotifier $notifier){$data=$request->validate(['requested_clock_in'=>'required|date','requested_break_in'=>'nullable|date','requested_break_out'=>'nullable|date|after_or_equal:requested_break_in','reason'=>'required|string|max:2000','request_type'=>['required',Rule::in(EditRequest::TYPES)],'break_reason'=>'nullable|string|max:255']);$requested=Carbon::parse($data['requested_clock_in']);if(!$requested->between(now()->startOfWeek(),now()->endOfWeek()))return back()->with('error','Requested time must be within the current week.');if($request->user()->editRequests()->whereBetween('created_at',[now()->startOfMonth(),now()->endOfMonth()])->count()>=EditRequest::MONTHLY_LIMIT)return back()->with('error','Monthly limit of 3 edit requests reached.');$clock=$request->user()->timeClocks()->whereDate('clock_in',$requested)->first()??$request->user()->timeClocks()->latest()->first();if(!$clock)return back()->with('error','No matching time record found.');$item=$request->user()->editRequests()->create($data+['time_clock_id'=>$clock->id,'department'=>$request->user()->department,'status'=>'pending_manager']);$notifier->created($item->load('user'),'attendance correction');return back()->with('success','Edit request submitted for manager or HOD approval.');}

    public function decision(Request $request,EditRequest $editRequest,string $decision,RequestNotifier $notifier,AttendanceCorrectionApplier $applier)
    {
        $this->authorize('decide',$editRequest);
        abort_unless(in_array($decision,['approve','reject'],true),404);
        $data=$request->validate(['note'=>'nullable|string|max:1000']);
        $previous=$editRequest->status;
        if(!in_array($previous,['pending','pending_manager'],true)&&!($previous==='pending_admin'&&$editRequest->approved_by_manager))return back()->with('error','This correction request is no longer awaiting approval.');
        if(!$editRequest->managerActionable()&&$previous!=='pending_admin')return back()->with('error','Requests older than one week cannot be changed.');

        DB::transaction(function()use($request,$editRequest,$decision,$data,$previous,$applier){
            $status=$decision==='approve'?'approved':'rejected';
            $editRequest->update(['approved_by_manager'=>$decision==='approve','manager_approved_by'=>$request->user()->id,'status'=>$status,'resolved_at'=>now(),'manager_note'=>$data['note']??null]);
            if($decision==='approve')$applier->apply($editRequest);
            ApprovalHistory::create(['approvable_type'=>EditRequest::class,'approvable_id'=>$editRequest->id,'acted_by'=>$request->user()->id,'stage'=>strtolower($request->user()->role),'action'=>$decision,'previous_status'=>$previous,'new_status'=>$status,'note'=>$data['note']??null]);
        });
        $notifier->decided($editRequest->load('user'),'attendance correction',$editRequest->status);
        return back()->with('success','Request '.$editRequest->status.'.');
    }
}
