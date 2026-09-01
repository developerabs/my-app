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
        Schema::create('products', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Basic Info
            $table->string('name');
            $table->string('short_name', 100)->nullable();
            $table->string('code')->unique();
            $table->string('hs_code')->nullable();
            $table->string('sku')->nullable();
            $table->string('slug')->unique();

            // Types & Symbology
            $table->enum('type', ['physical', 'service', 'digital', 'combo', 'dropship'])->default('physical');

            $table->enum('barcode_type', ['standard', 'barcode_with_batch', 'dynamic', 'master', 'barcode_with_serial'])->default('standard');
            $table->string('barcode_symbology')->default('C128');

            // Relations
            $table->foreignUuid('generic_id')->nullable()->constrained()->onDelete('set null');
            $table->string('drug_type', 50)->nullable()->default('tablet');
            $table->foreignUuid('brand_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('unit_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('base_unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->foreignUuid('sale_unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->foreignUuid('purchase_unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->json('unit_details')->nullable();

            // Pricing & Tax
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('wholesale_price', 15, 2)->nullable()->default(0);
            $table->integer('profit_margin')->default(0);
            $table->enum('tax_type', ['exclusive', 'inclusive'])->default('inclusive');
            $table->foreignUuid('tax_id')->nullable()->constrained()->onDelete('set null');

            // Inventory & Flags
            $table->boolean('manage_stock')->default(true);
            $table->boolean('allow_oversale')->default(false);
            $table->boolean('has_variants')->default(false);
            $table->boolean('has_imei')->default(false);
            $table->boolean('has_expire_date')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('has_specification')->default(false);
            $table->boolean('sale_online')->default(true);
            $table->boolean('has_warranty')->default(false);
            $table->boolean('enable_preorder')->default(false);
            $table->boolean('has_opening_stock')->default(false);
            // Stock
            $table->decimal('total_stock', 20, 8)->default(0);
            // Details
            $table->json('warranty_details')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('alert_quantity', 15, 2)->nullable()->default(0);
            $table->decimal('max_sale_commision', 5, 2)->nullable()->default(0);
            $table->decimal('weight', 15, 2)->nullable()->default(0.1);
            $table->json('product_seo')->nullable();

            $table->string('digital_file')->nullable();
            $table->string('digital_external_link')->nullable();

            $table->string('external_id')->nullable()->index();
            $table->string('source_from')->default('web');

            // Media
            $table->string('video_url')->nullable();
            $table->string('thumbnail')->nullable();

            $table->enum('status', ['active', 'incomplete', 'pending', 'draft', 'deactivated'])->default('active');

            // Metadata & Ownership
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // --- Optimized Indexing ---

            // Single column indexes for frequent searching/filtering
            $table->index('name');
            $table->index('sku');
            $table->index('type');
            $table->index('is_featured');
            $table->index('sale_online');
            $table->index('price');
            // Searching by SKU or Code is very common in POS/Inventory
            $table->index(['code', 'sku'], 'idx_products_identifiers');
            $table->index(['status', 'type'], 'idx_products_status_type');
            $table->index(['drug_type', 'status'], 'idx_products_drug_type_lookup');
            // Fast filtering for a specific Generic's active physical products (Crucial for Pharmacy POS)
            $table->index(['generic_id', 'status', 'type'], 'idx_products_generic_lookup');
            // Fast filtering for a specific Brand's active online/offline items
            $table->index(['brand_id', 'status', 'sale_online'], 'idx_products_brand_lookup');
            // Global query filtering combine flag states for dashboard dashboards or catalogs
            $table->index(['status', 'type', 'is_featured', 'sale_online'], 'idx_products_catalog_matrix');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
