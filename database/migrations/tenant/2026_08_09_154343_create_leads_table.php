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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Lead Classification
            $table->foreignUuid('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('lead_subject_id')
                ->nullable()
                ->constrained('lead_subjects')
                ->nullOnDelete();

            $table->foreignId('lead_source_id')
                ->nullable()
                ->constrained('lead_sources')
                ->nullOnDelete();

            $table->foreignId('status_id')
                ->nullable()
                ->constrained('statuses')
                ->nullOnDelete();

            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('assigned_to_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Lead Information
            $table->string('type')->default('lead');
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('effective_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('username')->unique()->nullable();
            $table->text('description')->nullable();
            $table->json('address')->nullable();
            $table->string('website')->nullable();

            // Lead Management
            $table->string('priority')->default('medium');

            // Single attachment
            $table->string('attachment')->nullable();

            // Expected business value
            $table->decimal('expected_value', 15, 2)->nullable();

            // Follow-up & Contact
            $table->dateTime('follow_up_date')->nullable();

            $table->boolean('is_failed')->default(false);
            $table->dateTime('failed_at')->nullable();

            // Customer Conversion
            $table->uuid('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->dateTime('converted_at')->nullable();

            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes()->index();
            $table->timestamps();

            $table->index('name');
            $table->index('company_name');
            $table->index('email');
            $table->index('phone');
            $table->index('status_id');
            $table->index('is_failed');
            $table->unique(['category_id', 'phone'], 'unique_category_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
