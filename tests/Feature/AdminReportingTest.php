<?php
namespace Tests\Feature;

use App\Models\{Department,TimeClock,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportingTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email,string $role='Executive',array $extra=[]):User{return User::create(['name'=>$email,'email'=>$email,'password'=>'password','role'=>$role,'shift_time'=>'08:00','employeed'=>true]+$extra);}

    public function test_unemployed_user_cannot_log_in():void
    {
        $user=$this->user('inactive@test.com');
        $user->update(['employeed'=>false]);
        $this->post(route('login.attempt'),['email'=>$user->email,'password'=>'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_timesheets_respect_per_page_and_keep_filters():void
    {
        $admin=$this->user('admin@test.com','admin');
        $employee=$this->user('employee@test.com');
        foreach(range(1,25) as $day)TimeClock::create(['user_id'=>$employee->id,'clock_in'=>now()->startOfMonth()->addDays($day-1),'status'=>'on_time']);
        $response=$this->actingAs($admin)->get(route('admin.timesheets',['per_page'=>20,'employee_id'=>$employee->id]));
        $response->assertOk()->assertViewHas('clocks',fn($clocks)=>$clocks->count()===20&&$clocks->total()===25);
    }

    public function test_analytics_cards_follow_employee_filter():void
    {
        $admin=$this->user('admin2@test.com','admin');
        $first=$this->user('first@test.com');
        $second=$this->user('second@test.com');
        TimeClock::create(['user_id'=>$first->id,'clock_in'=>now()->startOfDay()->setTime(8,0),'clock_out'=>now()->startOfDay()->setTime(17,0),'status'=>'on_time','total_duration'=>32400]);
        TimeClock::create(['user_id'=>$second->id,'clock_in'=>now()->startOfDay()->setTime(9,0),'clock_out'=>now()->startOfDay()->setTime(17,0),'status'=>'late','total_duration'=>28800]);
        $response=$this->actingAs($admin)->get(route('admin.analytics',['range'=>'today','employee_id'=>$first->id]));
        $response->assertOk()->assertViewHas('stats',fn($stats)=>$stats['records']===1&&$stats['on_time']===1&&$stats['late']===0&&$stats['employees']===1);
    }
}
