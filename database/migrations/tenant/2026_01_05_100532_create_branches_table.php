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
        Schema::create('branches', function (Blueprint $table) {
            // ID as UUID (SaaS & PostgreSQL Friendly)
            $table->uuid('id')->primary();

            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('branch_code', 20)->unique()->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Relation to Accounts 
            $table->unsignedBigInteger('default_acc')->nullable()->index();
            $table->unsignedBigInteger('currency_id')->nullable()->index();
            $table->string('timezone')->default('Asia/Dhaka')->nullable();
            $table->string('bin_number')->nullable();
            $table->string('branch_logo')->nullable();
            $table->json('branch_settings')->nullable();
            // Status with Performance Index
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['active', 'inactive', 'locked'])->default('active')->index();
            $table->timestamp('locked_at')->nullable();

            // Audit Trail (Essential for fast lookups even with Activity Log package)
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->unsignedBigInteger('deleted_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes()->index();

            // Composite index for fast searching active branches
            $table->index(['id', 'status', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
