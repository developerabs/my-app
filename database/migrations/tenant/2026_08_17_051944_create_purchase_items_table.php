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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // Batch & Expiry Management
            $table->foreignUuid('product_batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('manufacturing_date')->nullable();

            // Unit Hierarchies & Conversion
            $table->foreignUuid('purchase_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignUuid('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('conversion_factor', 15, 6)->default(1.000000); // Conversion ratio to Base Unit

            // Quantities
            $table->decimal('quantity', 15, 4)->default(1.0000);
            $table->decimal('received_qty', 15, 4)->default(1.0000);
            $table->decimal('base_quantity', 15, 4)->default(1.0000); // quantity * conversion_factor

            // Pricing & Valuation (Purchase Unit Level)
            $table->decimal('unit_cost', 15, 2)->default(0); // Purchase invoice unit cost
            $table->decimal('base_unit_cost', 15, 2)->default(0); // Cost in Base Unit
            $table->decimal('allocated_landed_cost', 15, 2)->default(0); // Share of shipping/customs per unit (IAS 2)
            $table->decimal('effective_unit_cost', 15, 2)->default(0); // (unit_cost + allocated_landed_cost)

            // Batch Selling Prices / MRP (Crucial for Retail, Pharma & FMCG)
            $table->decimal('batch_price', 15, 2)->nullable();
            $table->decimal('batch_wholesale_price', 15, 2)->nullable();

            // Line Discounts
            $table->string('discount_method', 20)->default('flat'); // flat, percentage
            $table->decimal('discount_rate', 8, 2)->default(0);
            $table->decimal('unit_discount', 15, 2)->default(0);
            $table->decimal('total_discount', 15, 2)->default(0);

            // Line Taxes
            $table->foreignUuid('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('tax_method', 20)->default('exclusive'); // exclusive, inclusive
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);

            // Line Subtotals
            $table->decimal('subtotal', 15, 2)->default(0); // Transaction Currency Subtotal
            $table->decimal('base_subtotal', 15, 2)->default(0); // Base Currency Subtotal

            // Serial Numbers & Custom Identifiers
            $table->text('imei_list')->nullable(); // Comma-separated or JSON list of serials/IMEIs
            $table->text('barcodes')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            // Performance Indexes
            $table->index(['purchase_id', 'product_id'], 'idx_pur_items_prod');
            $table->index('product_batch_id', 'idx_pur_items_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
