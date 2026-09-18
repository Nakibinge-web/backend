<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Alter the ENUM to include ADJUSTMENT
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('IN', 'OUT', 'ADJUSTMENT') NOT NULL");
    }

    public function down(): void
    {
        // Revert — any existing ADJUSTMENT rows will be truncated back to ''
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('IN', 'OUT') NOT NULL");
    }
};
