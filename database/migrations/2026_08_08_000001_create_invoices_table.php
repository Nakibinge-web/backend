<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('source_ref')->nullable();

            // Customer info (stored directly – not linked to customers table so
            // the invoice stands alone even if the customer record is deleted)
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_address')->nullable();

            // Financials
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);

            // "paid" | "partial" | "due"
            $table->string('payment_status')->default('paid');

            // JSON array of line items: [{description, quantity, price, subtotal}]
            $table->json('items')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
