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
        Schema::create('generics', function (Blueprint $table) {
            // UUID Primary key gets implicit B-Tree index automatically
            $table->uuid('id')->primary();

            // Unique constraint automatically creates unique index for fast lookups
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description')->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->string('external_id')->nullable();
            $table->string('source_from')->default('web'); // e.g., 'web', 'api', 'excel'

            // Unsigned BigInt types matching default users table ID for foreign references
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes()->index();
            $table->timestamps();

            // ------------------------------------------------------------------------
            // CUSTOM OPTIMIZED INDEXES FOR HIGH PERFORMANCE
            // ------------------------------------------------------------------------

            // Index for dashboard toggles/sorting, filtering active & featured generic medicines
            $table->index(['is_active', 'is_featured', 'sort_order'], 'generics_status_sort_index');

            // Index for syncing data from third-party APIs or bulk uploads (Excel)
            $table->index(['source_from', 'external_id'], 'generics_source_sync_index');

            // Composite index for audit trails and tracking changes over time
            $table->index(['created_by', 'updated_by'], 'generics_audit_user_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generics');
    }
};
