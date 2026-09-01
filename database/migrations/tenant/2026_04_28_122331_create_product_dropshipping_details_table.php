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
        Schema::create('product_dropshipping_details', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('product_id')->unique()->constrained()->onDelete('cascade');
            $table->string('platform_name')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('external_product_code')->nullable();
            $table->string('external_product_url')->nullable();
            $table->string('external_sku')->nullable();
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('buying_price', 15, 2)->default(0);
            $table->decimal('estimated_shipping_cost', 15, 2)->default(0);
            $table->integer('delivery_lead_time')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_dropshipping_details');
    }
};
