<?php
namespace App\Http\Controllers;

use App\Models\{Department,User};
use App\Services\ConfigurationResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request,ConfigurationResolver $resolver)
    {
        $user=$request->user();
        if($user->isSuperAdmin())return redirect()->route('admin.dashboard');
        $latest=$user->timeClocks()->with('breaks')->latest('clock_in')->first();
        $week=$user->timeClocks()->with('breaks')->whereBetween('clock_in',[now()->startOfWeek(),now()->startOfWeek()->addDays(4)->endOfDay()])->latest('clock_in')->get();
        $month=$user->timeClocks()->whereBetween('clock_in',[now()->startOfMonth(),now()->endOfMonth()])->get();
        $team=collect();
        if($user->hasPermission('team.view'))$team=$this->teamScope($user)->with(['timeClocks'=>fn($query)=>$query->whereDate('clock_in',today())->latest('clock_in')->with('breaks')])->get();
        $breakConfig=$latest?($latest->applied_configuration['breaks']??$resolver->resolve($user,'breaks',$latest->clock_in)):$resolver->resolve($user,'breaks',today());
        $availableBreaks=collect($breakConfig['value']['options']??[])->where('active',true);
        return view('dashboard.index',compact('user','latest','week','month','team','availableBreaks')+['u'=>$user]);
    }

    public function team(Request $request)
    {
        abort_unless($request->user()->hasPermission('team.view'),403);
        $filters=$request->validate([
            'q'=>'nullable|string|max:255','department_id'=>'nullable|integer|exists:departments,id',
            'state'=>['nullable',Rule::in(['working','break','off','not_in'])],
        ]);
        $scope=$this->teamScope($request->user());
        $departmentIds=(clone $scope)->whereNotNull('department_id')->distinct()->pluck('department_id');
        $query=$scope
            ->when($filters['q']??null,fn($q,$value)=>$q->where(fn($search)=>$search->where('name','like',"%$value%")->orWhere('email','like',"%$value%")))
            ->when($filters['department_id']??null,fn($q,$id)=>$q->where('department_id',$id));
        $this->applyTeamState($query,$filters['state']??null);
        $team=$query->with(['departmentRelation','timeClocks'=>fn($q)=>$q->whereDate('clock_in',today())->latest('clock_in')->with('breaks')])->orderBy('name')->get();
        $stats=['total'=>$team->count(),'working'=>0,'break'=>0,'off'=>0,'not_in'=>0];
        foreach($team as $member){$clock=$member->timeClocks->first();$state=!$clock?'not_in':($clock->clock_out?'off':($clock->current_state==='working'?'working':'break'));$stats[$state]++;}
        $departments=Department::whereIn('id',$departmentIds)->orderBy('name')->get(['id','name']);
        $scopeLabel=$request->user()->isSuperAdmin()?'Company-wide employees':'Employees in your reporting scope';
        return view('dashboard.team',compact('team','stats','departments','scopeLabel'));
    }

    public function teamMember(Request $request,User $employee)
    {
        abort_unless($request->user()->hasPermission('team.view'),403);
        abort_unless($this->teamScope($request->user())->whereKey($employee->id)->exists(),403);

        $filters=$request->validate([
            'range'=>['nullable',Rule::in(['month','7_days','30_days','custom'])],
            'month'=>'nullable|date_format:Y-m',
            'start_date'=>'nullable|required_if:range,custom|date',
            'end_date'=>'nullable|required_if:range,custom|date|after_or_equal:start_date',
            'status'=>['nullable',Rule::in(['on_time','late'])],
            'per_page'=>['nullable',Rule::in(['20','50','100'])],
        ]);

        $range=$filters['range']??'month';
        if($range==='7_days'){$end=today();$start=today()->subDays(6);}
        elseif($range==='30_days'){$end=today();$start=today()->subDays(29);}
        elseif($range==='custom'){$start=Carbon::parse($filters['start_date']);$end=Carbon::parse($filters['end_date']);}
        else{$start=Carbon::createFromFormat('Y-m',($filters['month']??now()->format('Y-m')))->startOfMonth();$end=$start->copy()->endOfMonth();}
        $start=$start->startOfDay();
        $end=$end->startOfDay();

        $base=$employee->timeClocks()->with('breaks')->whereBetween('clock_in',[$start->copy()->startOfDay(),$end->copy()->endOfDay()]);
        $summaryRecords=(clone $base)->get();
        $clocks=(clone $base)->when($filters['status']??null,fn($query,$status)=>$query->where('status',$status))->latest('clock_in')->paginate((int)($filters['per_page']??20))->withQueryString();
        $leaves=$employee->leaves()->where('status','approved')->whereDate('start_date','<=',$end)->whereDate('end_date','>=',$start)->get();
        $leaveDays=$leaves->sum(function($leave)use($start,$end){$from=$leave->start_date->greaterThan($start)?$leave->start_date:$start;$to=$leave->end_date->lessThan($end)?$leave->end_date:$end;$days=$from->diffInDays($to)+1;return $leave->leave_type==='half_day'?$days*.5:$days;});
        $stats=['records'=>$summaryRecords->count(),'on_time'=>$summaryRecords->where('status','on_time')->count(),'late'=>$summaryRecords->where('status','late')->count(),'worked_seconds'=>$summaryRecords->sum(fn($clock)=>$clock->workedSeconds()),'leave_days'=>$leaveDays];
        $employee->load('departmentRelation','manager');
        return view('dashboard.team-member',compact('employee','clocks','stats','start','end','range'));
    }

    private function teamScope(User $user):Builder
    {
        $query=User::query()->where('employeed',true)->where('role','!=','super_admin');
        if($user->isSuperAdmin()||$user->role==='Director')return $query;
        if($user->role==='Manager')return $query->where(fn($scope)=>$scope->whereKey($user->id)->orWhere(fn($reports)=>$reports->where('manager_id',$user->id)->where('role','Executive')));
        if($user->isHod()){
            $departmentIds=$user->managedDepartmentIds();
            return $query->where(fn($scope)=>$scope->whereKey($user->id)
                ->orWhere(fn($reports)=>$reports->where('manager_id',$user->id)->whereIn('role',['Manager','Executive']))
                ->orWhere(fn($department)=>$department->whereIn('department_id',$departmentIds)->whereIn('role',['Manager','Executive'])));
        }
        $query->where(fn($scope)=>$scope->whereKey($user->id)->orWhere('manager_id',$user->id));
        return $query;
    }

    private function applyTeamState(Builder $query,?string $state):void
    {
        if(!$state)return;
        if($state==='not_in'){$query->whereDoesntHave('timeClocks',fn($clock)=>$clock->whereDate('clock_in',today()));return;}
        if($state==='off'){$query->whereHas('timeClocks',fn($clock)=>$clock->whereDate('clock_in',today())->whereNotNull('clock_out'));return;}
        $query->whereHas('timeClocks',fn($clock)=>$clock->whereDate('clock_in',today())->whereNull('clock_out')->when($state==='break',fn($q)=>$q->where('current_state','!=','working'),fn($q)=>$q->where('current_state','working')));
    }
}
