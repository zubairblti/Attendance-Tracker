<?php

namespace Tests\Feature;

use App\Models\{Department,EditRequest,Leave,TimeClock,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalActionsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email,string $role,array $extra=[]):User
    {
        return User::create(['name'=>$email,'email'=>$email,'password'=>'password','role'=>$role,'shift_time'=>'09:00','employeed'=>true]+$extra);
    }

    public function test_approval_tables_show_buttons_without_optional_note_inputs():void
    {
        $manager=$this->user('manager@test.com','Manager');
        $employee=$this->user('employee@test.com','Executive',['manager_id'=>$manager->id]);
        $leave=Leave::create(['user_id'=>$employee->id,'leave_type'=>'casual','start_date'=>today(),'end_date'=>today(),'status'=>'pending_manager']);
        $clock=TimeClock::create(['user_id'=>$employee->id,'clock_in'=>now()->subHour()]);
        $edit=EditRequest::create(['user_id'=>$employee->id,'time_clock_id'=>$clock->id,'requested_clock_in'=>now(),'reason'=>'Correction','status'=>'pending_manager','request_type'=>'clock_in']);

        $this->actingAs($manager)->get(route('leaves.index'))->assertOk()->assertDontSee('Optional note')->assertSee(route('leaves.decision',[$leave,'approve']))->assertSee(route('leaves.decision',[$leave,'reject']));
        $this->actingAs($manager)->get(route('edits.index'))->assertOk()->assertDontSee('Optional note')->assertSee(route('edits.decision',[$edit,'approve']))->assertSee(route('edits.decision',[$edit,'reject']));
    }

    public function test_manager_no_longer_sees_actions_after_approval_and_approver_name_is_shown():void
    {
        $manager=$this->user('manager@test.com','Manager');
        $employee=$this->user('employee@test.com','Executive',['manager_id'=>$manager->id]);
        $leave=Leave::create(['user_id'=>$employee->id,'leave_type'=>'casual','start_date'=>today(),'end_date'=>today(),'status'=>'pending_manager']);

        $this->actingAs($manager)->from(route('leaves.index'))->patch(route('leaves.decision',[$leave,'approve']))->assertRedirect(route('leaves.index'));
        $this->assertSame('approved',$leave->fresh()->status);
        $this->actingAs($manager)->get(route('leaves.index'))->assertOk()->assertSee('Manager approved')->assertSee($manager->name)->assertDontSee(route('leaves.decision',[$leave,'approve']));

        $this->actingAs($manager)->from(route('leaves.index'))->patch(route('leaves.decision',[$leave,'approve']))->assertRedirect(route('leaves.index'))->assertSessionHas('error','This leave request is no longer awaiting approval.');
    }

    public function test_admin_can_monitor_leave_but_cannot_decide_it():void
    {
        $manager=$this->user('manager@test.com','Manager');
        $employee=$this->user('employee@test.com','Executive',['manager_id'=>$manager->id]);
        $admin=$this->user('admin@test.com','super_admin');
        $leave=Leave::create(['user_id'=>$employee->id,'leave_type'=>'casual','start_date'=>today(),'end_date'=>today(),'status'=>'pending_manager']);

        $this->actingAs($admin)->get(route('leaves.index'))->assertOk()->assertSee($employee->name)->assertDontSee(route('leaves.decision',[$leave,'approve']))->assertDontSee(route('leaves.decision',[$leave,'reject']));
        $this->actingAs($admin)->patch(route('leaves.decision',[$leave,'approve']))->assertForbidden();
    }

    public function test_assigned_hod_can_make_the_final_leave_decision():void
    {
        $department=Department::create(['name'=>'Sales','code'=>'SALES']);
        $hod=$this->user('hod@test.com','HOD');
        $department->hods()->attach($hod);
        $employee=$this->user('employee@test.com','Executive',['department_id'=>$department->id]);
        $leave=Leave::create(['user_id'=>$employee->id,'leave_type'=>'medical','start_date'=>today(),'end_date'=>today(),'status'=>'pending_manager']);

        $this->actingAs($hod)->patch(route('leaves.decision',[$leave,'reject']))->assertRedirect();
        $this->assertSame('rejected',$leave->fresh()->status);
        $this->actingAs($hod)->get(route('leaves.index'))->assertSee('HOD rejected')->assertSee($hod->name);
    }
}
