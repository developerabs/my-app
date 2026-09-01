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
        Schema::create('combo_product_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('combo_product_id')->constrained('products')->onDelete('cascade');

            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');

            $table->foreignUuid('unit_id')->nullable()->constrained('units');
            
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);

            $table->timestamps();

            $table->index(['combo_product_id', 'product_id'], 'combo_prod_item_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combo_product_items');
    }
};
