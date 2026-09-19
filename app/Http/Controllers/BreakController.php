<?php
namespace App\Http\Controllers;
use App\Models\TimeClock; use App\Models\WorkBreak; use App\Services\ConfigurationResolver; use Illuminate\Http\Request; use Illuminate\Validation\Rule;
class BreakController extends Controller {
 public function start(Request $r,TimeClock $timeClock,ConfigurationResolver $resolver){abort_unless($timeClock->user_id===$r->user()->id,403);$config=$timeClock->applied_configuration['breaks']??$resolver->resolve($r->user(),'breaks',$timeClock->clock_in);$allowed=collect($config['value']['options']??[])->where('active',true)->pluck('label')->all();$data=$r->validate(['break_type'=>['required',Rule::in($allowed)]]);if($timeClock->breaks()->whereNull('break_out')->exists())return back()->with('error','A break is already active.');$timeClock->breaks()->create(['break_in'=>now(),'break_type'=>$data['break_type']]);$state=match($data['break_type']){'Meeting'=>'Meeting','Downtime'=>'Downtime',default=>'On break'};$timeClock->update(['current_state'=>$state]);return back()->with('success','Break started.');}
 public function stop(Request $r,TimeClock $timeClock,WorkBreak $break){abort_unless($timeClock->user_id===$r->user()->id&&$break->time_clock_id===$timeClock->id,403);$break->update(['break_out'=>now()]);$timeClock->update(['current_state'=>'working']);return back()->with('success','Break ended.');}
}
