<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name');
            $table->string('barcode')->nullable()->after('sku');
            $table->string('unit')->nullable()->after('barcode');          // e.g. pcs, kg, litre
            $table->decimal('cost_price', 10, 2)->nullable()->after('price');
            $table->string('image_path')->nullable()->after('cost_price');
            $table->text('description')->nullable()->after('image_path');
            $table->boolean('track_expiry')->default(false)->after('description');

            // supplier_id was required before — make it nullable
            $table->foreignId('supplier_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sku', 'barcode', 'unit', 'cost_price', 'image_path', 'description', 'track_expiry']);
            $table->foreignId('supplier_id')->nullable(false)->change();
        });
    }
};
