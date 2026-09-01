<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_imei_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_imei_id')->constrained('product_imeis')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('event_type', 50)->index(); // e.g., 'sold', 'returned', 'damaged'
            $table->text('description')->nullable(); // Additional details about the event
            $table->string('causable_type')->nullable(); // For polymorphic relation (e.g., Sale, Return)
            $table->string('causable_id', 36)->nullable(); // For polymorphic relation

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['causable_type', 'causable_id']);
            $table->index(['product_imei_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_imei_logs');
    }
};
