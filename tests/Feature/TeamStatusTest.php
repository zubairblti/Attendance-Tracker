<?php

namespace Tests\Feature;

use App\Models\{Department,TimeClock,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamStatusTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email,string $role,array $extra=[]):User{return User::create(['name'=>$email,'email'=>$email,'password'=>'password','role'=>$role,'shift_time'=>'08:00','employeed'=>true]+$extra);}

    public function test_super_admin_sees_company_wide_team_but_not_itself():void
    {
        $admin=$this->user('super@test.com','super_admin');
        $employee=$this->user('employee@test.com','Executive');
        TimeClock::create(['user_id'=>$employee->id,'clock_in'=>now()->startOfDay()->setTime(8,0),'current_state'=>'working','status'=>'on_time']);

        $this->actingAs($admin)->get(route('team'))->assertOk()
            ->assertViewHas('team',fn($team)=>$team->count()===1&&$team->first()->is($employee))
            ->assertViewHas('stats',fn($stats)=>$stats['total']===1&&$stats['working']===1);
    }

    public function test_manager_sees_self_and_executive_reports_but_not_upstream_or_peer_managers():void
    {
        $department=Department::create(['name'=>'Web','code'=>'WEB','active'=>true]);
        $hod=$this->user('hod@test.com','HOD',['department_id'=>$department->id]);
        $manager=$this->user('manager@test.com','Manager',['department_id'=>$department->id,'manager_id'=>$hod->id]);
        $direct=$this->user('direct@test.com','Executive',['department_id'=>$department->id,'manager_id'=>$manager->id]);
        $peer=$this->user('peer@test.com','Manager',['department_id'=>$department->id,'manager_id'=>$hod->id]);

        $this->actingAs($manager)->get(route('team'))->assertViewHas('team',fn($team)=>$team->count()===2&&$team->contains($manager)&&$team->contains($direct)&&!$team->contains($hod)&&!$team->contains($peer));
    }

    public function test_hod_sees_self_and_lower_department_roles_but_not_own_director():void
    {
        $department=Department::create(['name'=>'Sales','code'=>'SALES','active'=>true]);
        $director=$this->user('director@test.com','Director',['department_id'=>$department->id]);
        $hod=$this->user('hod@test.com','HOD',['department_id'=>$department->id,'manager_id'=>$director->id]);
        $department->hods()->attach($hod);
        $manager=$this->user('manager@test.com','Manager',['department_id'=>$department->id,'manager_id'=>$hod->id]);
        $executive=$this->user('executive@test.com','Executive',['department_id'=>$department->id,'manager_id'=>$manager->id]);

        $this->actingAs($hod)->get(route('team'))->assertViewHas('team',fn($team)=>$team->count()===3&&$team->contains($hod)&&$team->contains($manager)&&$team->contains($executive)&&!$team->contains($director));
    }

    public function test_director_sees_every_active_non_super_admin_employee():void
    {
        $director=$this->user('director@test.com','Director');
        $manager=$this->user('manager@test.com','Manager',['manager_id'=>$director->id]);
        $executive=$this->user('executive@test.com','Executive',['manager_id'=>$manager->id]);
        $this->user('super@test.com','super_admin');

        $this->actingAs($director)->get(route('team'))->assertViewHas('team',fn($team)=>$team->count()===3&&$team->contains($director)&&$team->contains($manager)&&$team->contains($executive));
    }

    public function test_empty_team_page_displays_a_useful_empty_state():void
    {
        $admin=$this->user('empty@test.com','super_admin');
        $this->actingAs($admin)->get(route('team'))->assertOk()->assertSee('No active employees have been created yet.');
    }
}
