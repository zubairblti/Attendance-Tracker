<?php

namespace Tests\Feature;

use App\Models\{Department,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminScopeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email,string $role,array $extra=[]):User{return User::create(['name'=>$email,'email'=>$email,'password'=>'password','role'=>$role,'shift_time'=>'08:00','employeed'=>true]+$extra);}

    public function test_admin_dashboard_employee_count_excludes_super_admin():void
    {
        $superAdmin=$this->user('super@test.com','super_admin');

        $this->actingAs($superAdmin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats',fn($stats)=>$stats['employees']===0);

        $this->user('employee@test.com','Executive');
        $this->actingAs($superAdmin)->get(route('admin.dashboard'))
            ->assertViewHas('stats',fn($stats)=>$stats['employees']===1);
    }

    public function test_super_admin_is_excluded_from_employee_report_and_configuration_selectors():void
    {
        $superAdmin=$this->user('super2@test.com','super_admin');
        $employee=$this->user('employee2@test.com','Executive');

        $this->actingAs($superAdmin)->get(route('admin.timesheets'))
            ->assertViewHas('employees',fn($users)=>$users->pluck('id')->all()===[$employee->id]);
        $this->actingAs($superAdmin)->get(route('admin.analytics'))
            ->assertViewHas('employees',fn($users)=>$users->pluck('id')->all()===[$employee->id]);
        $this->actingAs($superAdmin)->get(route('admin.configurations.index'))
            ->assertViewHas('employees',fn($users)=>$users->pluck('id')->all()===[$employee->id]);
    }

    public function test_department_employee_count_excludes_super_admin():void
    {
        $department=Department::create(['name'=>'Web','code'=>'WEB','active'=>true]);
        $this->user('super3@test.com','super_admin',['department_id'=>$department->id]);
        $this->user('employee3@test.com','Executive',['department_id'=>$department->id]);
        $this->assertSame(1,$department->assignedUsers()->count());
    }
}
