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
        Schema::create('brands', function (Blueprint $table) {
            // English: Use UUID for global scalability and security
            $table->uuid('id')->primary();
            
            // English: Basic Information
            $table->string('name');
            $table->string('slug')->unique(); // Unique index automatically created
            $table->string('website_url')->nullable();
            $table->text('description')->nullable();
            
            // English: Media and Branding
            $table->string('brand_logo')->nullable();
            $table->string('cover_image')->nullable();
            
            // English: E-commerce and POS specific settings
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            
            // English: SEO Metadata for E-commerce
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            // English: Integration and Source tracking
            $table->string('external_id')->nullable(); 
            $table->string('source_from')->default('web'); // e.g., 'web', 'api', 'excel'

            // English: Audit Trails and User Tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes()->index();
            $table->timestamps();

            // --- English: Optimized Indexing Strategy ---

            // English: Index for searching brands by name and status (Common in POS/Admin)
            $table->index(['name', 'is_active']);

            // English: Composite index for Frontend/E-commerce Homepage (Featured & Sorting)
            // This allows database to fetch active featured brands sorted by order in one go.
            $table->index(['is_active', 'is_featured', 'sort_order']);

            // English: Index for external system lookups
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
