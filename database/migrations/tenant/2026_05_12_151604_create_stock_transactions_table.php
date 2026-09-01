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
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignUuid('product_batch_id')->nullable()->constrained('product_batches')->nullOnDelete();

            $table->enum('type', ['in', 'out']);
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('stock_after', 15, 2)->default(0)->comment('Stock after this transaction is applied');

            $table->nullableUuidMorphs('referenceable');

            $table->text('note')->nullable();
            $table->date('transaction_date')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'product_id']);
            $table->index(['referenceable_id', 'referenceable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
