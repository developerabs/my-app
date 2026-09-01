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
        Schema::create('memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->comment('Membership plan name (e.g., Basic, Pro, Enterprise)');
            $table->string('code')->unique()->comment('Unique code for the membership plan');

            $table->decimal('membership_fee', 12, 2)->default(0.00); // 8,2 is a bit small for high currency values
            $table->decimal('minimum_spend', 12, 2)->default(0.00);
            $table->integer('minimum_points')->default(0);
            $table->integer('validation_days')->default(365);
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 12, 2)->default(0.00);
            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);

            // Auditing columns
            // English comment: Index added to 'created_by' and 'deleted_at' for faster filtering and soft-delete queries
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes()->index();

            // Adding Indexes for performance
            $table->index('name'); // Frequent searching/sorting by name
            $table->index('is_active'); // Filtering active/inactive plans
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
