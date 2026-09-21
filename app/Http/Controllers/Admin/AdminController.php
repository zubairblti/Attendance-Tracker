<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Department,EditRequest,Leave,TimeClock,User};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private function guard(Request $request,string $permission='dashboard.view'):void{abort_unless($request->user()->hasPermission($permission),403);}
    public function dashboard(Request $request){$this->guard($request);$stats=['employees'=>User::where('employeed',true)->where('role','!=','super_admin')->count(),'working'=>TimeClock::whereNull('clock_out')->whereHas('user',fn($q)=>$q->where('role','!=','super_admin'))->count(),'pending_leaves'=>Leave::whereIn('status',['pending','pending_manager','pending_admin'])->whereHas('user',fn($q)=>$q->where('role','!=','super_admin'))->count(),'pending_edits'=>EditRequest::whereIn('status',['pending','pending_manager','pending_admin'])->whereHas('user',fn($q)=>$q->where('role','!=','super_admin'))->count()];$active=TimeClock::with('user','breaks')->whereNull('clock_out')->whereHas('user',fn($q)=>$q->where('role','!=','super_admin'))->latest()->get();return view('admin.dashboard',compact('stats','active'));}
    public function users(Request $request){$this->guard($request,'employees.manage');return redirect()->route('admin.employees.index');}

    public function timesheets(Request $request)
    {
        $this->guard($request,'attendance.view');
        $filters=$request->validate([
            'date'=>'nullable|date','employee_id'=>'nullable|integer|exists:users,id',
            'department_id'=>'nullable|integer|exists:departments,id','status'=>['nullable',Rule::in(['on_time','late'])],
            'per_page'=>['nullable',Rule::in(['20','50','100'])],
        ]);
        $perPage=(int)($filters['per_page']??20);
        $clocks=TimeClock::with('user.departmentRelation','breaks')->whereHas('user',fn($q)=>$q->where('role','!=','super_admin'))
            ->when($filters['date']??null,fn($q,$value)=>$q->whereDate('clock_in',$value))
            ->when($filters['employee_id']??null,fn($q,$value)=>$q->where('user_id',$value))
            ->when($filters['department_id']??null,fn($q,$value)=>$q->whereHas('user',fn($u)=>$u->where('department_id',$value)))
            ->when($filters['status']??null,fn($q,$value)=>$q->where('status',$value))
            ->latest('clock_in')->paginate($perPage)->withQueryString();
        $employees=User::where('employeed',true)->where('role','!=','super_admin')->orderBy('name')->get(['id','name']);
        $departments=Department::where('active',true)->orderBy('name')->get(['id','name']);
        return view('admin.timesheets',compact('clocks','employees','departments','perPage'));
    }

    public function live(Request $request){$this->guard($request);$active=TimeClock::with('user','breaks')->whereNull('clock_out')->latest()->get()->map(fn($clock)=>['name'=>$clock->user->name,'department'=>$clock->user->department,'clock_in'=>$clock->clock_in?->format('h:i A'),'state'=>$clock->current_state,'worked'=>$clock->formattedDuration()]);return response()->json(['active'=>$active,'count'=>$active->count(),'updated_at'=>now()->toIso8601String()]);}
}
