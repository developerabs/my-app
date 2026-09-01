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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->foreignUuid('membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->string('name')->comment('Customer name');
            $table->string('email')->nullable();
            $table->string('phone')->unique()->index();

            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->integer('total_points')->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->timestamp('last_transaction_date')->nullable();

            $table->json('membership_details')->nullable();

            $table->boolean('is_active')->default(true);

            // English: Integration and Source tracking
            $table->string('external_id')->nullable(); 
            $table->string('source_from')->default('web'); // e.g., 'web', 'api', 'excel'
            // Auditing columns
            // English comment: Index added to 'created_by' and 'deleted_at' for faster filtering and soft-delete queries
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
