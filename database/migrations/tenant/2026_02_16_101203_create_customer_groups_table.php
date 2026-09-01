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
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 10, 2)->default(0.00);
            $table->decimal('min_order_amount', 10, 2)->default(0.00);

            // English comment: Active and default status are frequently used in WHERE clauses
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();

            // Auditing columns
            // English comment: Index added to 'created_by' and 'deleted_at' for faster filtering and soft-delete queries
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes()->index();

            // Manual Indexing for searching and performance
            // English comment: Name index for faster searches. Soft-delete index is usually handled by Laravel but good to keep in mind for composite indexes.
            $table->index('name');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
