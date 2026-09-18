<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'purchases.edit',   'display_name' => 'Edit Purchases',   'group' => 'purchases'],
            ['name' => 'purchases.delete', 'display_name' => 'Delete Purchases', 'group' => 'purchases'],
        ];

        foreach ($permissions as $perm) {
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
            ->whereIn('name', ['purchases.edit', 'purchases.delete'])
            ->delete();
    }
};
