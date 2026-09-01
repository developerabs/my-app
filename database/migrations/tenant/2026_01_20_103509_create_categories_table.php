<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Category Types Table
        Schema::create('category_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // exml: product, service
            $table->string('display_name');  // exml: Product, Service
            $table->timestamps();
        });

        // Main Categories Table
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable(); // Foreign key নিচে ডিফাইন করা আছে

            $table->string('external_id')->nullable(); // Index সরিয়ে কম্পোজিটে রাখা হয়েছে
            $table->string('source_from')->nullable()->default('web');

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->foreignId('category_type_id')->constrained('category_types')->onDelete('cascade');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes()->index(); // English: Essential index for almost every query

            // --- Optimized Composite Indexes ---

            // English: Index for main category listing & filtering
            $table->index(['category_type_id', 'is_active', 'parent_id'], 'category_main_query_index');

            // English: Index for external sync (keeps both fields indexed together)
            $table->index(['external_id', 'source_from'], 'external_sync_index');
        });

        // Self-referencing foreign key (Parent ID)
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['category_type_id']);
        });
        Schema::dropIfExists('categories');
        Schema::dropIfExists('category_types');
    }
};
