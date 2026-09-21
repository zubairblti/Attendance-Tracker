<?php
namespace Tests\Feature;

use App\Models\{EditRequest,Leave,Notification,TimeClock,User};
use App\Services\RequestNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_notifications_show_request_details_not_configuration_placeholders():void
    {
        Event::fake();
        $manager=User::create(['name'=>'Manager','email'=>'manager@test.com','password'=>'password','role'=>'Manager','shift_time'=>'09:00','employeed'=>true]);
        $admin=User::create(['name'=>'System Admin','email'=>'admin@test.com','password'=>'password','role'=>'super_admin','shift_time'=>'09:00','employeed'=>true]);
        $employee=User::create(['name'=>'Employee','email'=>'employee@test.com','password'=>'password','role'=>'Executive','shift_time'=>'09:00','employeed'=>true,'manager_id'=>$manager->id]);
        $clock=TimeClock::create(['user_id'=>$employee->id,'clock_in'=>now()->subHour()]);
        $edit=EditRequest::create(['user_id'=>$employee->id,'time_clock_id'=>$clock->id,'requested_clock_in'=>now(),'reason'=>'Clock device failed','status'=>'pending_manager','request_type'=>'clock_tower_not_working']);
        app(RequestNotifier::class)->created($edit->load('user'),'attendance correction');

        $notification=$manager->notifications()->first();
        $this->assertSame('Clock Tower Not Working',$notification->details['request_type']);
        $this->assertSame(0,$admin->notifications()->count());
        $this->actingAs($manager)->get(route('notifications.index'))->assertOk()->assertSee('Clock Tower Not Working')->assertSee('Clock device failed')->assertDontSee('PREVIOUS CONFIGURATION')->assertDontSee('Initial configuration');
    }

    public function test_existing_request_notification_without_details_does_not_show_fake_configuration():void
    {
        $user=User::create(['name'=>'Manager','email'=>'manager2@test.com','password'=>'password','role'=>'Manager','shift_time'=>'09:00','employeed'=>true]);
        Notification::create(['user_id'=>$user->id,'type'=>'request_created','title'=>'New leave request','message'=>'Employee submitted a leave request.','url'=>'/leaves']);
        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('New leave request')->assertDontSee('PREVIOUS CONFIGURATION')->assertDontSee('Initial configuration');
    }
}
