<?php
namespace App\Http\Controllers;
use App\Services\ConfigurationResolver; use Illuminate\Http\Request; use Illuminate\Support\Carbon;
class TimeClockController extends Controller {
 public function clockIn(Request $r,ConfigurationResolver $resolver){
  $u=$r->user();abort_if($u->isSuperAdmin(),403,'Super Admin accounts do not record attendance.');$now=now();
  $windows=collect([$now->copy()->subDay(),$now])->map(function($day)use($u,$resolver){$shift=$resolver->resolve($u,'shift',$day);$working=$resolver->resolve($u,'working_days',$day);$v=$shift['value'];$start=Carbon::parse($day->toDateString().' '.$v['start_time']);$end=Carbon::parse($day->toDateString().' '.$v['end_time']);if($end->lte($start))$end->addDay();return ['start'=>$start,'end'=>$end,'early'=>(int)($v['early_clock_in_minutes']??30),'grace'=>(int)($v['grace_minutes']??1),'working'=>in_array($day->dayOfWeekIso,$working['value']['weekdays']??[1,2,3,4,5]),'snapshot'=>$resolver->attendanceSnapshot($u,$day)];});
  $chosen=$windows->first(fn($w)=>$w['working']&&$now->between($w['start']->copy()->subMinutes($w['early']),$w['end']));if(!$chosen)return back()->with('error','You cannot clock in outside an active working-day shift window.');if($u->timeClocks()->whereBetween('clock_in',[$chosen['start'],$chosen['end']])->exists())return back()->with('error','You have already clocked in for this shift.');
  $u->timeClocks()->create(['clock_in'=>$now,'status'=>$now->gt($chosen['start']->copy()->addMinutes($chosen['grace']))?'late':'on_time','current_state'=>'working','ip_address'=>$r->ip(),'applied_configuration'=>$chosen['snapshot']]);return back()->with('success','Clocked in successfully.');
 }
 public function clockOut(Request $r){$tc=$r->user()->timeClocks()->whereNull('clock_out')->with('breaks')->latest()->first();if(!$tc)return back()->with('error','No active shift found.');$now=now();if($active=$tc->activeBreak())$active->update(['break_out'=>$now]);$seconds=$tc->workedSeconds($now);$tc->update(['clock_out'=>$now,'total_duration'=>$seconds,'break_duration'=>$tc->totalBreakSeconds($now),'current_state'=>'off']);return back()->with('success','Clocked out successfully.');}
 public function show(Request $r,string $date){$tc=$r->user()->timeClocks()->with('breaks')->whereDate('clock_in',$date)->firstOrFail();return view('time_clocks.show',compact('tc'));}
}
