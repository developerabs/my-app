<?php

use App\Enums\ImeiStatus;
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
        Schema::create('product_imeis', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignUuid('product_batch_id')->nullable()->constrained('product_batches')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('imei_number', 100)->unique();
            $table->string('status', 30)->default(ImeiStatus::AVAILABLE->value)->index(); // Available, Sold, Returned, etc.
            $table->string('sourceable_type')->nullable(); // For polymorphic relation (e.g., Sale, Return)
            $table->string('sourceable_id', 36)->nullable(); // For polymorphic relation
            $table->string('actionable_type')->nullable();
            $table->string('actionable_id', 36)->nullable();
            $table->timestamps();

            $table->index(['sourceable_type', 'sourceable_id']);
            $table->index(['actionable_type', 'actionable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_imeis');
    }
};
