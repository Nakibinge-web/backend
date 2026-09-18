<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create a default tenant
        $tenant = Tenant::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'    => 'Demo Business',
                'email'   => 'admin@example.com',
                'phone'   => '+1 000 000 0000',
                'address' => '123 Main Street',
            ]
        );

        // 2. Create the owner/admin user for this tenant
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'tenant_id' => $tenant->id,
                'name'      => 'Admin User',
                'email'     => 'admin@example.com',
                'password'  => Hash::make('password'),
            ]
        );

        // 3. Assign the default 'owner' role (seeded by migration, tenant_id = null)
        $ownerRole = Role::where('name', 'owner')->whereNull('tenant_id')->first();
        if ($ownerRole && ! $user->roles()->where('role_id', $ownerRole->id)->exists()) {
            $user->roles()->attach($ownerRole->id);
        }

        // 4. Attach all default permissions to the owner role
        if ($ownerRole) {
            $allPermissions = Permission::whereNull('tenant_id')->pluck('id');
            $ownerRole->permissions()->syncWithoutDetaching($allPermissions);
        }

        $this->command->info('✅ Seeded tenant: Demo Business');
        $this->command->info('✅ Seeded admin user: admin@example.com / password');
    }
}
