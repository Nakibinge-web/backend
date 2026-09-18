<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false); // true = system-wide default role
            $table->timestamps();

            $table->unique(['tenant_id', 'name']); // prevent duplicate names per tenant
        });

        // Seed default roles (tenant_id = null means available to all tenants)
        DB::table('roles')->insert([
            ['tenant_id' => null, 'name' => 'owner',   'description' => 'Business owner with full access',     'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => null, 'name' => 'admin',   'description' => 'Administrator with broad permissions', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => null, 'name' => 'manager', 'description' => 'Manager with operational access',      'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => null, 'name' => 'cashier', 'description' => 'Cashier with sales access only',       'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
