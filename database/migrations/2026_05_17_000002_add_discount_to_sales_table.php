<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('discount_type')->nullable()->after('customer_id'); // 'percent' | 'fixed'
            $table->decimal('discount_amount', 15, 2)->nullable()->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_amount']);
        });
    }
};
