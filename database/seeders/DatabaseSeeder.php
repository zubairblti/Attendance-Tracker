<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed only the initial administrator required to access a fresh CRM.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email'=>'admin@example.com'],
            [
                'name'=>'System Admin',
                'password'=>'password',
                'role'=>'super_admin',
                'department'=>null,
                'department_id'=>null,
                'manager_id'=>null,
                'shift_time'=>'18:00',
                'status'=>'active',
                'employeed'=>true,
            ]
        );
    }
}
