<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\GamificationSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Disable foreign key constraints during seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // =========================================================================
        // SYSTEM FOUNDER PORTAL LOGIN CONFIGURATION
        // =========================================================================

        // 1. Recreate the Global Super Admin Role (school_id is set to null!)
        DB::table('roles')->insertOrIgnore([
            'id' => 2,
            'name' => 'super_admin',
            'guard_name' => 'web',
            'school_id' => null, // NULL designates a global/central system-wide role
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Recreate the Central Founder User account
        DB::table('users')->insertOrIgnore([
            'id' => 1,
            'school_id' => null, // NULL designates a central/system-wide user
            'name' => 'System Founder',
            'email' => 'twaynehlatywayo09@gmail.com', // Super Admin login email
            'password' => Hash::make('securepassword'), // securepassword
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Assign the global 'super_admin' role to User ID 1 (System Founder)
        DB::table('model_has_roles')->insertOrIgnore([
            'role_id' => 2,
            'model_type' => 'App\Models\User',
            'model_id' => 1,
        ]);

        DB::table('model_has_roles')->insertOrIgnore([
            'role_id' => 2,
            'model_type' => 'Modules\Users\Models\User',
            'model_id' => 1,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->call([
            GamificationSeeder::class,
        ]);
    }
}
