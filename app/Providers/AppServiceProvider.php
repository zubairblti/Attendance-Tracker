<?php

namespace App\Providers;

use App\Models\OrganizationSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use App\Models\{Department,EditRequest,Leave,User};
use App\Policies\{DepartmentPolicy,EditRequestPolicy,LeavePolicy,UserPolicy};
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class,UserPolicy::class);Gate::policy(Department::class,DepartmentPolicy::class);Gate::policy(Leave::class,LeavePolicy::class);Gate::policy(EditRequest::class,EditRequestPolicy::class);
        View::composer('*',function($view){$view->with('organizationSetting',Schema::hasTable('organization_settings')?OrganizationSetting::current():null);});
    }
}
