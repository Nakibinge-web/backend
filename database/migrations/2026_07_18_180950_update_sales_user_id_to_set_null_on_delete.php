<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Drop the existing foreign key constraint (cascade on delete)
            $table->dropForeign(['user_id']);

            // Make user_id nullable so it can be set to NULL when the user is deleted
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Re-add the foreign key with SET NULL on delete
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['user_id']);

            // Restore original: not nullable, cascade on delete
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }
};
