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
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignUuid('product_batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->string('barcode');
            $table->enum('barcode_type', ['standard', 'barcode_with_batch', 'dynamic', 'master', 'barcode_with_serial'])->default('standard');
            $table->string('barcode_symbology')->default('C128');
            $table->string('display_name')->nullable();
            $table->string('sku')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
    }
};
