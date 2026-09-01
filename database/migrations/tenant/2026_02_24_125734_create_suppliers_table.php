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
        Schema::create('suppliers', function (Blueprint $table) {
            // English: Primary key using UUID
            $table->uuid('id')->primary();
            $table->string('name');

            // English: Indexing phone and email instead of unique for soft-delete compatibility
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable()->index();

            $table->string('company_name')->nullable()->index();
            $table->string('company_tax_id')->nullable();

            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->timestamp('last_transaction_date')->nullable();

            $table->json('address')->nullable();
            $table->json('bank_details')->nullable();

            // English: Added index to is_active for faster filtering in POS/Purchase dropdowns
            $table->boolean('is_active')->default(true)->index();

            $table->string('external_id')->nullable()->index();
            $table->string('source_from')->default('web');

            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            // English: Auditing columns
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            // English: SoftDeletes must be indexed for better performance on large datasets
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
