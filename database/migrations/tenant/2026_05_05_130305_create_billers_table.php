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
        Schema::create('billers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Basic Info - Search optimization needed for name and phone
            $table->string('name')->unique();
            $table->string('company_name')->nullable()->index(); // Index for faster searching by company
            $table->string('propiter_name')->nullable();
            $table->string('phone')->index(); // Index added: frequently used for lookup
            $table->string('email')->nullable()->index(); // Index added: for login or contact search

            $table->text('address')->nullable();
            $table->string('bin')->nullable()->index(); // Index added: NBR compliance frequently checks BIN

            $table->string('logo')->nullable();
            $table->string('website_url')->nullable();
            $table->string('certificate')->nullable();
            $table->text('tnc')->nullable()->comment('Terms & Conditions');

            $table->json('meta')->nullable();

            // Audit Trails (Foreign Keys are indexed by default in Laravel constrained)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_active')->default(true)->index(); // Index added: filtering active billers is common

            $table->timestamps();
            $table->softDeletes();
            

            // Composite Index: If you often filter active billers by company or phone
            $table->index(['is_active', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billers');
    }
};
