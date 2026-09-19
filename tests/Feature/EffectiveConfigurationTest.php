<?php

namespace Tests\Feature;

use App\Events\ConfigurationChanged;
use App\Models\{ConfigurationAudit,ConfigurationVersion,Department,User};
use App\Services\{ConfigurationManager,ConfigurationResolver};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EffectiveConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_shift_history_is_resolved_by_attendance_date(): void
    {
        Event::fake();
        $admin=$this->user('admin@test.com','super_admin',null);
        $employee=$this->user('sales@test.com','Executive','Sales');
        $department=Department::create(['name'=>'Sales','code'=>'SALES']);
        $manager=app(ConfigurationManager::class);
        $base=['configuration_type'=>'shift','scope_type'=>'department','scope_id'=>$department->id];
        $manager->createVersion($base+['effective_from'=>'2026-09-01','value'=>['start_time'=>'07:00','end_time'=>'15:00','grace_minutes'=>1,'early_clock_in_minutes'=>30]],$admin);
        $manager->createVersion($base+['effective_from'=>'2026-09-16','value'=>['start_time'=>'08:00','end_time'=>'16:00','grace_minutes'=>1,'early_clock_in_minutes'=>30]],$admin);

        $resolver=app(ConfigurationResolver::class);
        $this->assertSame('07:00',$resolver->resolve($employee,'shift','2026-09-10')['value']['start_time']);
        $this->assertSame('08:00',$resolver->resolve($employee,'shift','2026-09-20')['value']['start_time']);
        $this->assertSame('16:00',$resolver->attendanceSnapshot($employee,\Illuminate\Support\Carbon::parse('2026-09-20'))['attendance']['value']['auto_clock_out_time']);
        $this->assertSame('2026-09-15',ConfigurationVersion::oldest('effective_from')->first()->effective_to->toDateString());
    }

    public function test_department_notifications_only_target_affected_users_and_admins(): void
    {
        Event::fake();
        $admin=$this->user('admin@test.com','super_admin',null);
        $sales=$this->user('sales@test.com','Executive','Sales');
        $unrelated=$this->user('web@test.com','Executive','Web');
        $hod=$this->user('hod@test.com','HOD',null);
        $department=Department::create(['name'=>'Sales','code'=>'SALES']);$department->hods()->attach($hod);
        app(ConfigurationManager::class)->createVersion(['configuration_type'=>'working_days','scope_type'=>'department','scope_id'=>$department->id,'effective_from'=>'2026-09-16','value'=>['weekdays'=>[1,2,3,4,5,6]]],$admin);

        $this->assertSame(1,$admin->notifications()->count());
        $this->assertSame(1,$sales->notifications()->count());
        $this->assertSame(1,$hod->notifications()->count());
        $this->assertSame(0,$unrelated->notifications()->count());
        Event::assertDispatched(ConfigurationChanged::class,fn($event)=>count($event->recipientIds)===3);
    }

    public function test_same_effective_date_updates_version_and_preserves_audit_trail(): void
    {
        Event::fake();
        $admin=$this->user('admin@test.com','super_admin',null);
        $manager=app(ConfigurationManager::class);
        $base=['configuration_type'=>'shift','scope_type'=>'global','scope_id'=>null,'effective_from'=>'2026-09-19'];
        $manager->createVersion($base+['value'=>['start_time'=>'07:00','end_time'=>'15:00']],$admin);
        $manager->createVersion($base+['value'=>['start_time'=>'08:00','end_time'=>'16:00']],$admin);

        $this->assertSame(1,ConfigurationVersion::count());
        $this->assertSame('08:00',ConfigurationVersion::first()->value['start_time']);
        $this->assertSame(2,ConfigurationAudit::count());
        $this->assertSame('07:00',ConfigurationAudit::latest('id')->first()->previous_value['start_time']);
    }

    private function user(string $email,string $role,?string $department):User
    {
        return User::create(['name'=>$email,'email'=>$email,'password'=>'password','role'=>$role,'department'=>$department,'shift_time'=>'07:00','employeed'=>true]);
    }
}
