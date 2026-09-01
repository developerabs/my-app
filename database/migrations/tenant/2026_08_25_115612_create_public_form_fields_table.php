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
        Schema::create('public_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_form_id')->constrained('public_forms')->cascadeOnDelete();
            $table->string('name'); // Ex: 'name', 'email', 'phone', 'expected_value'
            $table->string('label'); // Ex: 'Full Name', 'Email Address', 'Phone Number'
            $table->string('type')->default('text'); // Ex: text, email, number, select, textarea, file, date
            $table->text('options')->nullable(); // Select type হলে অপশনগুলো রাখার জন্য (JSON/Array)
            $table->string('placeholder')->nullable();
            $table->integer('column_width')->default(1); // ফিল্ডের কলাম প্রস্থ (1-12)

            $table->boolean('is_default_required')->default(false); // বাই ডিফল্ট রিকোয়ার্ড কিনা
            $table->boolean('is_system_defined')->default(false); // সিস্টেমের নিজস্ব ফিল্ড কিনা (যা লিড টেবিলের সাথে সরাসরি ম্যাপ করা)
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_table')->default(false);
            $table->boolean('searchable')->default(false);
            $table->boolean('filterable')->default(false);

            // Extra Settings / Customizations
            $table->json('meta')->nullable();

            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->integer('sort_order')->default(0); // ফিল্ডগুলোর সিকোয়েন্স বা পজিশন
            $table->softDeletes()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_form_fields');
    }
};
