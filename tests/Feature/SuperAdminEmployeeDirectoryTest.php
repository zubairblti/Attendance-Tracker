<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminEmployeeDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email,string $role):User{return User::create(['name'=>$email,'email'=>$email,'password'=>'password','role'=>$role,'shift_time'=>'08:00','employeed'=>true]);}

    public function test_super_admin_is_not_shown_in_employee_directory():void
    {
        $superAdmin=$this->user('super@test.com','super_admin');
        $employee=$this->user('employee@test.com','Executive');

        $this->actingAs($superAdmin)->get(route('admin.employees.index'))
            ->assertOk()
            ->assertViewHas('users',fn($users)=>$users->total()===1&&$users->first()->is($employee));
    }

    public function test_super_admin_cannot_be_unemployed_or_deleted_through_employee_routes():void
    {
        $actor=$this->user('actor@test.com','super_admin');
        $target=$this->user('target@test.com','super_admin');

        $this->actingAs($actor)->patch(route('admin.employees.toggle',$target))->assertForbidden();
        $this->actingAs($actor)->delete(route('admin.employees.destroy',$target))->assertForbidden();
        $this->assertDatabaseHas('users',['id'=>$target->id,'employeed'=>true]);
    }
}
