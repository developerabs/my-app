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
        Schema::create('asset_register_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_register_id')->constrained('asset_registers')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            
            // Supplier reference for purchased assets (Supports line-by-line vendor selection)
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            
            // Link to the auto-generated Vendor Bill
            $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();

            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('remaining_quantity', 18, 4)->default(0);
            
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('base_unit_cost', 18, 4)->default(0);
            $table->decimal('total_cost', 18, 4)->default(0);
            $table->decimal('base_total_cost', 18, 4)->default(0);
            
            // Instant payment tracking per line
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->decimal('base_paid_amount', 18, 4)->default(0);

            $table->decimal('salvage_value', 18, 4)->default(0);
            $table->decimal('base_salvage_value', 18, 4)->default(0);
            $table->unsignedInteger('useful_life')->default(5);
            $table->date('depreciation_start_date')->nullable();
            
            $table->timestamps();

            $table->index('asset_register_id');
            $table->index('asset_id');
            $table->index('supplier_id');
            $table->index('bill_id');
            $table->index(['asset_id', 'depreciation_start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_register_items');
    }
};
