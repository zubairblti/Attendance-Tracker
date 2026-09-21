<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_only_the_admin_login():void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users',1);
        $this->assertDatabaseHas('users',[
            'email'=>'admin@example.com',
            'role'=>'super_admin',
            'employeed'=>true,
        ]);
        $this->assertDatabaseCount('departments',0);
        $this->assertDatabaseCount('time_clocks',0);
        $this->assertDatabaseCount('leaves',0);
        $this->assertDatabaseCount('edit_requests',0);
        $this->assertDatabaseCount('configuration_versions',0);
    }
}
