<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support MODIFY COLUMN, so we need to check the driver
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'admin', 'manager', 'cashier') NOT NULL DEFAULT 'cashier'");
        } elseif ($driver === 'sqlite') {
            // For SQLite, we need to recreate the table
            // But since this migration is between creating and dropping the role column,
            // and we're using the role_user pivot table now, we can skip this for SQLite
            // The role column will be dropped in a later migration anyway
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'manager', 'cashier') NOT NULL DEFAULT 'cashier'");
        }
    }
};
