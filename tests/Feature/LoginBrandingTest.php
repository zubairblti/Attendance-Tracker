<?php

namespace Tests\Feature;

use App\Models\OrganizationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_configured_organization_branding():void
    {
        OrganizationSetting::create(['name'=>'CoderKod','tagline'=>'Build better','primary_color'=>'#123456','accent_color'=>'#654321']);

        $this->get(route('login'))->assertOk()
            ->assertSee('<span class="brand-mark large">C</span>',false)
            ->assertSee('CoderKod team')
            ->assertSee('Build better')
            ->assertSee('Sign in · CoderKod');
    }
}
