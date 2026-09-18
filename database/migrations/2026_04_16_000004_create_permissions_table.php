<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');           // e.g. 'products.create'
            $table->string('display_name')->nullable(); // e.g. 'Create Products'
            $table->string('group')->nullable();        // e.g. 'products', 'sales'
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        // Seed default permissions grouped by resource
        $defaults = [
            // Products
            ['name' => 'products.view',   'display_name' => 'View Products',    'group' => 'products'],
            ['name' => 'products.create', 'display_name' => 'Create Products',  'group' => 'products'],
            ['name' => 'products.edit',   'display_name' => 'Edit Products',    'group' => 'products'],
            ['name' => 'products.delete', 'display_name' => 'Delete Products',  'group' => 'products'],
            // Categories
            ['name' => 'categories.view',   'display_name' => 'View Categories',   'group' => 'categories'],
            ['name' => 'categories.create', 'display_name' => 'Create Categories', 'group' => 'categories'],
            ['name' => 'categories.edit',   'display_name' => 'Edit Categories',   'group' => 'categories'],
            ['name' => 'categories.delete', 'display_name' => 'Delete Categories', 'group' => 'categories'],
            // Customers
            ['name' => 'customers.view',   'display_name' => 'View Customers',   'group' => 'customers'],
            ['name' => 'customers.create', 'display_name' => 'Create Customers', 'group' => 'customers'],
            ['name' => 'customers.edit',   'display_name' => 'Edit Customers',   'group' => 'customers'],
            ['name' => 'customers.delete', 'display_name' => 'Delete Customers', 'group' => 'customers'],
            // Suppliers
            ['name' => 'suppliers.view',   'display_name' => 'View Suppliers',   'group' => 'suppliers'],
            ['name' => 'suppliers.create', 'display_name' => 'Create Suppliers', 'group' => 'suppliers'],
            ['name' => 'suppliers.edit',   'display_name' => 'Edit Suppliers',   'group' => 'suppliers'],
            ['name' => 'suppliers.delete', 'display_name' => 'Delete Suppliers', 'group' => 'suppliers'],
            // Sales
            ['name' => 'sales.view',   'display_name' => 'View Sales',   'group' => 'sales'],
            ['name' => 'sales.create', 'display_name' => 'Create Sales', 'group' => 'sales'],
            ['name' => 'sales.edit',   'display_name' => 'Edit Sales',   'group' => 'sales'],
            ['name' => 'sales.delete', 'display_name' => 'Delete Sales', 'group' => 'sales'],
            ['name' => 'sales.report', 'display_name' => 'Sales Reports','group' => 'sales'],
            // Purchases
            ['name' => 'purchases.view',   'display_name' => 'View Purchases',   'group' => 'purchases'],
            ['name' => 'purchases.create', 'display_name' => 'Create Purchases', 'group' => 'purchases'],
            ['name' => 'purchases.edit',   'display_name' => 'Edit Purchases',   'group' => 'purchases'],
            ['name' => 'purchases.delete', 'display_name' => 'Delete Purchases', 'group' => 'purchases'],
            ['name' => 'purchases.report', 'display_name' => 'Purchase Reports', 'group' => 'purchases'],
            // Stock
            ['name' => 'stock.view',   'display_name' => 'View Stock Movements', 'group' => 'stock'],
            // Users
            ['name' => 'users.view',   'display_name' => 'View Users',   'group' => 'users'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'group' => 'users'],
            ['name' => 'users.edit',   'display_name' => 'Edit Users',   'group' => 'users'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'group' => 'users'],
            // Roles
            ['name' => 'roles.view',   'display_name' => 'View Roles',   'group' => 'roles'],
            ['name' => 'roles.create', 'display_name' => 'Create Roles', 'group' => 'roles'],
            ['name' => 'roles.edit',   'display_name' => 'Edit Roles',   'group' => 'roles'],
            ['name' => 'roles.delete', 'display_name' => 'Delete Roles', 'group' => 'roles'],
        ];

        foreach ($defaults as $perm) {
            DB::table('permissions')->insert(array_merge($perm, [
                'tenant_id'  => null,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
