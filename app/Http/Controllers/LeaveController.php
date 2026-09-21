<?php
namespace App\Http\Controllers;

use App\Models\{ApprovalHistory,Leave};
use App\Services\RequestNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    public function mine(Request $request){$leaves=$request->user()->leaves()->latest()->paginate(20);return view('leaves.mine',compact('leaves'));}
    public function index(Request $request){$this->authorize('viewAny',Leave::class);$user=$request->user();$query=Leave::with(['user','histories.actor']);if(!$user->hasPermission('requests.finalize'))$query->whereHas('user',fn($employee)=>$employee->where('manager_id',$user->id)->orWhereIn('department_id',$user->hodDepartments()->pluck('departments.id')));$leaves=$query->latest()->paginate(20);return view('leaves.index',compact('leaves'));}
    public function store(Request $request,RequestNotifier $notifier){$data=$request->validate(['leave_type'=>'required|in:medical,casual,half_day,emergency','start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date','reason'=>'nullable|string|max:2000','medical_certificate'=>'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120']);if($request->hasFile('medical_certificate'))$data['medical_certificate_path']=$request->file('medical_certificate')->store('medical-certificates','public');$leave=$request->user()->leaves()->create($data+['status'=>'pending_manager']);$notifier->created($leave->load('user'),'leave');return back()->with('success','Leave request submitted for manager approval.');}

    public function decision(Request $request,Leave $leave,string $decision,RequestNotifier $notifier)
    {
        $this->authorize('decide',$leave);
        abort_unless(in_array($decision,['approve','reject'],true),404);
        $data=$request->validate(['note'=>'nullable|string|max:1000']);
        $previous=$leave->status;
        if(!in_array($previous,['pending_manager','pending'],true))return back()->with('error','This leave request is no longer awaiting approval.');

        $status=$decision==='approve'?'approved':'rejected';
        $leave->update(['approved_by_manager'=>$decision==='approve','manager_approved_by'=>$request->user()->id,'approved_by'=>$request->user()->id,'status'=>$status,'resolved_at'=>now(),'decision_note'=>$data['note']??null]);
        $stage=strtolower($request->user()->role);
        ApprovalHistory::create(['approvable_type'=>Leave::class,'approvable_id'=>$leave->id,'acted_by'=>$request->user()->id,'stage'=>$stage,'action'=>$decision,'previous_status'=>$previous,'new_status'=>$status,'note'=>$data['note']??null]);
        $notifier->decided($leave->load('user'),'leave',$status);
        return back()->with('success','Leave request '.str_replace('_',' ',$status).'.');
    }

    public function certificate(Request $request,Leave $leave){$this->authorize('viewAny',Leave::class);abort_unless($leave->medical_certificate_path&&Storage::disk('public')->exists($leave->medical_certificate_path),404);return Storage::disk('public')->response($leave->medical_certificate_path);}
}
