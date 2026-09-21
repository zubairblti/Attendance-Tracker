<?php

namespace Tests\Feature;

use App\Models\{Department,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDirectoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email,string $role,array $extra=[]):User{return User::create(['name'=>$email,'email'=>$email,'password'=>'password','role'=>$role,'shift_time'=>'08:00','employeed'=>true]+$extra);}

    public function test_employee_directory_filters_by_department_and_manager():void
    {
        $admin=$this->user('admin@test.com','super_admin');
        $web=Department::create(['name'=>'Web','code'=>'WEB','active'=>true]);
        $seo=Department::create(['name'=>'SEO','code'=>'SEO','active'=>true]);
        $manager=$this->user('manager@test.com','Manager',['department_id'=>$web->id]);
        $match=$this->user('match@test.com','Executive',['department_id'=>$web->id,'manager_id'=>$manager->id]);
        $this->user('other@test.com','Executive',['department_id'=>$seo->id]);

        $this->actingAs($admin)->get(route('admin.employees.index',['department_id'=>$web->id,'leader_id'=>$manager->id]))
            ->assertOk()->assertViewHas('users',fn($users)=>$users->total()===1&&$users->first()->is($match));
    }

    public function test_hod_filter_includes_employees_in_assigned_departments():void
    {
        $admin=$this->user('admin2@test.com','super_admin');
        $department=Department::create(['name'=>'Sales','code'=>'SALES','active'=>true]);
        $hod=$this->user('hod@test.com','HOD');
        $hod->hodDepartments()->attach($department);
        $employee=$this->user('sales@test.com','Executive',['department_id'=>$department->id]);

        $this->actingAs($admin)->get(route('admin.employees.index',['leader_id'=>$hod->id]))
            ->assertViewHas('users',fn($users)=>$users->total()===1&&$users->first()->is($employee));
    }
}
