<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->date('manufacture_date')->nullable()->after('track_expiry');
            $table->date('expiry_date')->nullable()->after('manufacture_date');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['manufacture_date', 'expiry_date']);
        });
    }
};
