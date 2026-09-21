<?php

namespace Tests\Feature;

use App\Models\{Department,EditRequest,TimeClock,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email,string $role,array $extra=[]):User
    {
        return User::create(['name'=>$email,'email'=>$email,'password'=>'password','role'=>$role,'shift_time'=>'08:00','employeed'=>true]+$extra);
    }

    public function test_manager_clock_in_approval_is_final_and_recalculates_late_status():void
    {
        Event::fake();
        $manager=$this->user('manager@test.com','Manager');
        $employee=$this->user('employee@test.com','Executive',['manager_id'=>$manager->id]);
        $clock=TimeClock::create(['user_id'=>$employee->id,'clock_in'=>today()->setTime(9,0),'clock_out'=>today()->setTime(17,0),'status'=>'late','total_duration'=>28800,'applied_configuration'=>['shift'=>['value'=>['start_time'=>'08:00','grace_minutes'=>5]]]]);
        $request=EditRequest::create(['user_id'=>$employee->id,'time_clock_id'=>$clock->id,'requested_clock_in'=>today()->setTime(8,3),'reason'=>'Device issue','status'=>'pending_manager','request_type'=>'clock_in']);

        $this->actingAs($manager)->patch(route('edits.decision',[$request,'approve']))->assertRedirect();
        $this->assertSame('approved',$request->fresh()->status);
        $this->assertSame($manager->id,$request->fresh()->manager_approved_by);
        $this->assertSame('on_time',$clock->fresh()->status);
        $this->assertSame(today()->setTime(8,3)->timestamp,$clock->fresh()->clock_in->timestamp);
        $this->assertSame(32220,$clock->fresh()->total_duration);
    }

    public function test_manager_break_approval_is_final_and_recalculates_worked_time():void
    {
        Event::fake();
        $manager=$this->user('manager2@test.com','Manager');
        $employee=$this->user('employee2@test.com','Executive',['manager_id'=>$manager->id]);
        $clock=TimeClock::create(['user_id'=>$employee->id,'clock_in'=>today()->setTime(8,0),'clock_out'=>today()->setTime(17,0),'status'=>'on_time','total_duration'=>32400,'break_duration'=>0]);
        $request=EditRequest::create(['user_id'=>$employee->id,'time_clock_id'=>$clock->id,'requested_clock_in'=>today()->setTime(8,0),'requested_break_in'=>today()->setTime(12,0),'requested_break_out'=>today()->setTime(12,30),'reason'=>'Forgot break','status'=>'pending_manager','request_type'=>'forgot_break_start']);

        $this->actingAs($manager)->patch(route('edits.decision',[$request,'approve']))->assertRedirect();
        $this->assertSame('approved',$request->fresh()->status);
        $this->assertSame(1800,$clock->fresh()->break_duration);
        $this->assertSame(30600,$clock->fresh()->total_duration);
        $this->assertDatabaseHas('breaks',['time_clock_id'=>$clock->id]);
    }

    public function test_hod_can_finalize_clock_tower_request():void
    {
        Event::fake();
        $department=Department::create(['name'=>'Sales','code'=>'SALES']);
        $hod=$this->user('hod@test.com','HOD');
        $department->hods()->attach($hod);
        $employee=$this->user('employee3@test.com','Executive',['department_id'=>$department->id]);
        $clock=TimeClock::create(['user_id'=>$employee->id,'clock_in'=>today()->setTime(9,0),'status'=>'late','applied_configuration'=>['shift'=>['value'=>['start_time'=>'08:00','grace_minutes'=>5]]]]);
        $request=EditRequest::create(['user_id'=>$employee->id,'time_clock_id'=>$clock->id,'requested_clock_in'=>today()->setTime(8,3),'reason'=>'Clock tower failed','status'=>'pending_manager','request_type'=>'clock_tower_not_working']);

        $this->actingAs($hod)->patch(route('edits.decision',[$request,'approve']))->assertRedirect();
        $this->assertSame('approved',$request->fresh()->status);
        $this->assertSame('on_time',$clock->fresh()->status);
        $this->assertDatabaseHas('approval_histories',['approvable_id'=>$request->id,'acted_by'=>$hod->id,'new_status'=>'approved']);
    }

    public function test_admin_cannot_view_or_decide_any_attendance_correction():void
    {
        Event::fake();
        $employee=$this->user('employee4@test.com','Executive');
        $admin=$this->user('admin@test.com','super_admin');
        $clock=TimeClock::create(['user_id'=>$employee->id,'clock_in'=>today()->setTime(9,0)]);
        foreach(['clock_in','clock_tower_not_working','forgot_break_start','forgot_break_end'] as $type){
            $request=EditRequest::create(['user_id'=>$employee->id,'time_clock_id'=>$clock->id,'requested_clock_in'=>today()->setTime(8,0),'reason'=>'Correction','status'=>'pending_manager','request_type'=>$type]);
            $this->actingAs($admin)->patch(route('edits.decision',[$request,'approve']))->assertForbidden();
        }
        $this->actingAs($admin)->get(route('edits.index'))->assertForbidden();
    }

    public function test_already_manager_approved_legacy_request_can_be_finalized_by_manager():void
    {
        Event::fake();
        $manager=$this->user('manager3@test.com','Manager');
        $employee=$this->user('employee5@test.com','Executive',['manager_id'=>$manager->id]);
        $clock=TimeClock::create(['user_id'=>$employee->id,'clock_in'=>today()->setTime(9,0),'status'=>'late','applied_configuration'=>['shift'=>['value'=>['start_time'=>'08:00','grace_minutes'=>5]]]]);
        $request=EditRequest::create(['user_id'=>$employee->id,'time_clock_id'=>$clock->id,'requested_clock_in'=>today()->setTime(8,3),'reason'=>'Legacy request','status'=>'pending_admin','approved_by_manager'=>true,'request_type'=>'clock_in']);

        $this->actingAs($manager)->get(route('edits.index'))->assertOk()->assertSee(route('edits.decision',[$request,'approve']));
        $this->actingAs($manager)->patch(route('edits.decision',[$request,'approve']))->assertRedirect();
        $this->assertSame('approved',$request->fresh()->status);
    }
}
