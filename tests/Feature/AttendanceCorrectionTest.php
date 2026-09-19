<?php
namespace Tests\Feature;

use App\Models\{EditRequest,TimeClock,User,WorkBreak};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email,string $role):User{return User::create(['name'=>$email,'email'=>$email,'password'=>'password','role'=>$role,'shift_time'=>'08:00','employeed'=>true]);}

    public function test_approved_clock_in_correction_recalculates_late_status_and_duration():void
    {
        Event::fake();
        $employee=$this->user('employee@test.com','Executive');
        $admin=$this->user('admin@test.com','admin');
        $clock=TimeClock::create(['user_id'=>$employee->id,'clock_in'=>today()->setTime(9,0),'clock_out'=>today()->setTime(17,0),'status'=>'late','total_duration'=>28800,'applied_configuration'=>['shift'=>['value'=>['start_time'=>'08:00','grace_minutes'=>5]]]]);
        $request=EditRequest::create(['user_id'=>$employee->id,'time_clock_id'=>$clock->id,'requested_clock_in'=>today()->setTime(8,3),'reason'=>'Device issue','status'=>'pending_admin','request_type'=>'clock_in']);

        $this->actingAs($admin)->patch(route('edits.decision',[$request,'approve']))->assertRedirect();

        $clock->refresh();
        $this->assertSame('on_time',$clock->status);
        $this->assertSame(today()->setTime(8,3)->timestamp,$clock->clock_in->timestamp);
        $this->assertSame(32220,$clock->total_duration);
    }

    public function test_approved_break_correction_recalculates_break_and_worked_duration():void
    {
        Event::fake();
        $employee=$this->user('employee2@test.com','Executive');
        $admin=$this->user('admin2@test.com','admin');
        $clock=TimeClock::create(['user_id'=>$employee->id,'clock_in'=>today()->setTime(8,0),'clock_out'=>today()->setTime(17,0),'status'=>'on_time','total_duration'=>32400,'break_duration'=>0]);
        $request=EditRequest::create(['user_id'=>$employee->id,'time_clock_id'=>$clock->id,'requested_clock_in'=>today()->setTime(8,0),'requested_break_in'=>today()->setTime(12,0),'requested_break_out'=>today()->setTime(12,30),'reason'=>'Forgot break','status'=>'pending_admin','request_type'=>'forgot_break_start']);

        $this->actingAs($admin)->patch(route('edits.decision',[$request,'approve']))->assertRedirect();

        $clock->refresh();
        $this->assertSame(1800,$clock->break_duration);
        $this->assertSame(30600,$clock->total_duration);
        $this->assertDatabaseHas('breaks',['time_clock_id'=>$clock->id]);
    }
}
