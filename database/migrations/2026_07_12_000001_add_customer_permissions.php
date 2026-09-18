<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'customers.view',   'display_name' => 'View Customers',   'group' => 'customers'],
            ['name' => 'customers.create', 'display_name' => 'Create Customers', 'group' => 'customers'],
            ['name' => 'customers.edit',   'display_name' => 'Edit Customers',   'group' => 'customers'],
            ['name' => 'customers.delete', 'display_name' => 'Delete Customers', 'group' => 'customers'],
        ];

        foreach ($permissions as $perm) {
            // Skip if it already exists (idempotent)
            $exists = DB::table('permissions')
                ->whereNull('tenant_id')
                ->where('name', $perm['name'])
                ->exists();

            if (!$exists) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'tenant_id'  => null,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereNull('tenant_id')
            ->whereIn('name', ['customers.view', 'customers.create', 'customers.edit', 'customers.delete'])
            ->delete();
    }
};
