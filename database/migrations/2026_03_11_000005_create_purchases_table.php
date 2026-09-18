<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 15, 2);
            $table->date('purchase_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};

