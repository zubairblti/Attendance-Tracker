<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Department,TimeClock,User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function analytics(Request $request)
    {
        abort_unless($request->user()->hasPermission('attendance.view'),403);
        $filters=$request->validate([
            'range'=>['nullable',Rule::in(['today','week','month','custom'])],
            'start_date'=>'nullable|date','end_date'=>'nullable|date|after_or_equal:start_date',
            'employee_id'=>'nullable|integer|exists:users,id','department_id'=>'nullable|integer|exists:departments,id',
            'status'=>['nullable',Rule::in(['on_time','late'])],
        ]);
        $range=$filters['range']??'month';
        [$start,$end]=$this->analyticsPeriod($range,$filters);
        $query=$this->filteredClocks($filters)->whereBetween('clock_in',[$start,$end]);
        $records=(clone $query)->with('breaks')->get();
        $daily=(clone $query)->selectRaw('DATE(clock_in) day, COUNT(*) total, SUM(CASE WHEN status="late" THEN 1 ELSE 0 END) late, SUM(CASE WHEN status="on_time" THEN 1 ELSE 0 END) on_time, COALESCE(SUM(total_duration),0) worked_seconds')->groupBy('day')->orderBy('day')->get();
        $eligible=$this->eligibleUsers($filters);
        $workingDays=$this->workingDays($start,$end);
        $expected=$eligible->count()*$workingDays;
        $attendanceDays=(clone $query)->selectRaw('user_id, DATE(clock_in) day')->distinct()->get()->count();
        $stats=['records'=>$records->count(),'employees'=>$records->pluck('user_id')->unique()->count(),'late'=>$records->where('status','late')->count(),'on_time'=>$records->where('status','on_time')->count(),'worked_seconds'=>$records->sum(fn($clock)=>$clock->workedSeconds()),'absent'=>max(0,$expected-$attendanceDays)];
        $employees=User::where('employeed',true)->orderBy('name')->get(['id','name']);
        $departments=Department::where('active',true)->orderBy('name')->get(['id','name']);
        $scopeMaximum=max(1,$eligible->count());
        return view('admin.analytics',compact('daily','stats','employees','departments','filters','range','start','end','scopeMaximum'));
    }

    public function missing(Request $request){abort_unless($request->user()->hasPermission('attendance.view'),403);$date=Carbon::parse($request->date??today());$users=$this->missingUsers($date);return view('admin.missing',compact('users','date'));}
    public function leaveSummary(Request $request){abort_unless($request->user()->hasPermission('attendance.view'),403);$users=User::where('employeed',true)->withCount(['leaves as medical_used'=>fn($q)=>$q->where(['leave_type'=>'medical','status'=>'approved']),'leaves as casual_used'=>fn($q)=>$q->where(['leave_type'=>'casual','status'=>'approved']),'leaves as half_days'=>fn($q)=>$q->where(['leave_type'=>'half_day','status'=>'approved'])])->paginate(25);return view('admin.leave-summary',compact('users'));}

    public function export(Request $request,string $range)
    {
        abort_unless($request->user()->hasPermission('reports.export'),403);
        [$start,$end]=$this->period($range,$request);
        $rows=$this->filteredClocks($request->only(['employee_id','department_id','status']))->with('user')->whereBetween('clock_in',[$start,$end])->get();
        return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['Employee','Email','Department','Date','Clock In','Clock Out','Worked Seconds','Status']);foreach($rows as $clock)fputcsv($out,[$clock->user->name,$clock->user->email,$clock->user->department,$clock->clock_in?->toDateString(),$clock->clock_in?->format('H:i'),$clock->clock_out?->format('H:i'),$clock->workedSeconds(),$clock->status]);fclose($out);},'attendance-'.$range.'.csv',['Content-Type'=>'text/csv']);
    }

    public function lateAbsent(Request $request){abort_unless($request->user()->hasPermission('reports.export'),403);$date=Carbon::parse($request->date??today());$late=TimeClock::with('user')->whereDate('clock_in',$date)->where('status','late')->get();$absent=$this->missingUsers($date);return response()->streamDownload(function()use($late,$absent,$date){$out=fopen('php://output','w');fputcsv($out,['Type','Employee','Department','Date']);foreach($late as $clock)fputcsv($out,['Late',$clock->user->name,$clock->user->department,$date->toDateString()]);foreach($absent as $user)fputcsv($out,['Absent',$user->name,$user->department,$date->toDateString()]);fclose($out);},'late-absent-'.$date->toDateString().'.csv');}

    private function filteredClocks(array $filters):Builder{return TimeClock::query()->when($filters['employee_id']??null,fn($q,$id)=>$q->where('user_id',$id))->when($filters['department_id']??null,fn($q,$id)=>$q->whereHas('user',fn($u)=>$u->where('department_id',$id)))->when($filters['status']??null,fn($q,$status)=>$q->where('status',$status));}
    private function eligibleUsers(array $filters){return User::where('employeed',true)->whereNotIn('role',['super_admin','admin','manage_admin','qa_admin'])->when($filters['employee_id']??null,fn($q,$id)=>$q->whereKey($id))->when($filters['department_id']??null,fn($q,$id)=>$q->where('department_id',$id))->get();}
    private function workingDays(Carbon $start,Carbon $end):int{$days=0;for($day=$start->copy()->startOfDay();$day->lte($end);$day->addDay())if($day->isWeekday())$days++;return $days;}
    private function analyticsPeriod(string $range,array $filters):array{$now=now();return match($range){'today'=>[$now->copy()->startOfDay(),$now->copy()->endOfDay()],'week'=>[$now->copy()->startOfWeek(),$now->copy()->endOfWeek()],'custom'=>[Carbon::parse($filters['start_date']??today())->startOfDay(),Carbon::parse($filters['end_date']??today())->endOfDay()],default=>[$now->copy()->startOfMonth(),$now->copy()->endOfMonth()]};}
    private function period(string $range,Request $request):array{$now=now();return match($range){'today'=>[$now->copy()->startOfDay(),$now->copy()->endOfDay()],'week'=>[$now->copy()->startOfWeek(),$now->copy()->endOfWeek()],'month'=>[$now->copy()->startOfMonth(),$now->copy()->endOfMonth()],'quarter'=>[$now->copy()->firstOfQuarter(),$now->copy()->lastOfQuarter()],'half-year'=>[$now->month<=6?$now->copy()->startOfYear():$now->copy()->month(7)->startOfMonth(),$now->month<=6?$now->copy()->month(6)->endOfMonth():$now->copy()->endOfYear()],'filters'=>[Carbon::parse($request->start_date??today())->startOfDay(),Carbon::parse($request->end_date??today())->endOfDay()],default=>abort(404)};}
    private function missingUsers(Carbon $date){return User::where('employeed',true)->whereNotIn('role',['super_admin','admin','manage_admin','qa_admin'])->whereDoesntHave('timeClocks',fn($q)=>$q->whereDate('clock_in',$date))->get();}
}
