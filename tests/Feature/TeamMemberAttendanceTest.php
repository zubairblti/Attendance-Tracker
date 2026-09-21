<?php

namespace Tests\Feature;

use App\Models\{Department,Leave,TimeClock,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email,string $role,array $extra=[]):User
    {
        return User::create(['name'=>$email,'email'=>$email,'password'=>'password','role'=>$role,'shift_time'=>'09:00','employeed'=>true]+$extra);
    }

    public function test_manager_can_open_direct_report_attendance_with_filtered_summary():void
    {
        $department=Department::create(['name'=>'Web','code'=>'WEB']);
        $manager=$this->user('manager@test.com','Manager',['department_id'=>$department->id,'department'=>'Web']);
        $employee=$this->user('employee@test.com','Executive',['department_id'=>$department->id,'department'=>'Web','manager_id'=>$manager->id]);
        TimeClock::create(['user_id'=>$employee->id,'clock_in'=>now()->startOfMonth()->addDays(2)->setTime(9,0),'clock_out'=>now()->startOfMonth()->addDays(2)->setTime(17,0),'status'=>'on_time','current_state'=>'off']);
        TimeClock::create(['user_id'=>$employee->id,'clock_in'=>now()->startOfMonth()->addDays(3)->setTime(9,30),'clock_out'=>now()->startOfMonth()->addDays(3)->setTime(17,0),'status'=>'late','current_state'=>'off']);
        Leave::create(['user_id'=>$employee->id,'leave_type'=>'casual','start_date'=>now()->startOfMonth()->addDays(5),'end_date'=>now()->startOfMonth()->addDays(6),'status'=>'approved']);

        $this->actingAs($manager)->get(route('team.member',[$employee,'range'=>'month','month'=>now()->format('Y-m')]))
            ->assertOk()->assertSee($employee->name)->assertSee('WORKING HOURS')->assertSee('APPROVED LEAVE')->assertSee('15h 30m')->assertSee('2.0')->assertSee(now()->daysInMonth.' calendar days');
    }

    public function test_manager_cannot_open_employee_outside_reporting_scope():void
    {
        $manager=$this->user('manager@test.com','Manager');
        $employee=$this->user('unrelated@test.com','Executive');
        $this->actingAs($manager)->get(route('team.member',$employee))->assertForbidden();
    }

    public function test_manager_cannot_open_own_hod_but_can_open_self():void
    {
        $hod=$this->user('hod@test.com','HOD');
        $manager=$this->user('manager@test.com','Manager',['manager_id'=>$hod->id]);
        $this->actingAs($manager)->get(route('team.member',$hod))->assertForbidden();
        $this->actingAs($manager)->get(route('team.member',$manager))->assertOk();
    }
}
