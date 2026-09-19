<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TimeClock extends Model
{
    protected $fillable=['user_id','clock_in','clock_out','total_duration','status','break_duration','current_state','ip_address','applied_configuration'];
    protected function casts():array{return ['clock_in'=>'datetime','clock_out'=>'datetime','applied_configuration'=>'array'];}
    public function user(){return $this->belongsTo(User::class);}
    public function employee(){return $this->user();}
    public function breaks(){return $this->hasMany(WorkBreak::class);}
    public function editRequests(){return $this->hasMany(EditRequest::class);}
    public function activeBreak():?WorkBreak{return $this->breaks->whereNull('break_out')->last();}
    public function totalBreakSeconds(?Carbon $now=null):int{$now??=now();return (int)$this->breaks->reject(fn($b)=>$b->break_type==='Meeting')->sum(fn($b)=>$b->break_in?$b->break_in->diffInSeconds($b->break_out??$now):0);}
    public function workedSeconds(?Carbon $now=null):int{$now??=now();return $this->clock_in?max(0,(int)$this->clock_in->diffInSeconds($this->clock_out??$now)-$this->totalBreakSeconds($now)):0;}
    public function formattedDuration(?int $seconds=null):string{$seconds??=$this->workedSeconds();return sprintf('%dh %02dm',intdiv($seconds,3600),intdiv($seconds%3600,60));}

    public function recalculateAfterCorrection():void
    {
        $this->load('breaks');
        $shift=$this->applied_configuration['shift']['value']??[];
        $start=Carbon::parse($this->clock_in->toDateString().' '.($shift['start_time']??$this->user->shift_time?->format('H:i')??'18:00'));
        $grace=(int)($shift['grace_minutes']??1);
        $values=['status'=>$this->clock_in->gt($start->copy()->addMinutes($grace))?'late':'on_time'];
        if($this->clock_out){$values['break_duration']=$this->totalBreakSeconds($this->clock_out);$values['total_duration']=$this->workedSeconds($this->clock_out);}
        $this->update($values);
    }
}
