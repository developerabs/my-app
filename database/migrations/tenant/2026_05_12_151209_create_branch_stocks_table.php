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
        Schema::create('branch_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('shelf_id')->nullable()->constrained('shelves')->nullOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignUuid('product_batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();

            // --- Optimized High-Performance Indexing ---
            // English Comment: CRUCIAL FOR POS SCANNER - Fast lookup combining branch, product, and specific variant
            $table->index(['branch_id', 'product_id', 'product_variant_id'], 'idx_branch_prod_variant_search');

            // English Comment: Fast indexing for generic search and alert quantities on dashboard
            $table->index(['branch_id', 'product_id'], 'idx_branch_product_search');
            
            // English Comment: Directly lookup where a specific item is placed within a branch
            $table->index(['branch_id', 'shelf_id'], 'idx_branch_shelf_combiner');
            
            // English Comment: FEFO/FIFO implementation - Find stock based on branch and near-to-expire batches fast
            $table->index(['branch_id', 'product_batch_id'], 'idx_branch_batch_search');

            // English Comment: Composite index for stock updates instead of standard unique to handle multi-null parameters safely
            $table->index(['branch_id', 'product_id', 'product_variant_id', 'product_batch_id'], 'idx_branch_stock_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_stocks');
    }
};
