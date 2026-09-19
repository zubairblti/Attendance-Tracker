<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_user_can_log_in_and_view_dashboard(): void
    {
        $user = User::create(['name'=>'Demo Employee','email'=>'demo@example.com','password'=>'secret123','role'=>'Executive','department'=>'WEB','shift_time'=>'18:00']);
        $this->post('/login',['email'=>$user->email,'password'=>'secret123'])->assertRedirect('/');
        $this->actingAs($user)->get('/')->assertOk()->assertSee('This week');
    }
}
