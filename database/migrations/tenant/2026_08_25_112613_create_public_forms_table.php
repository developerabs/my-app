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
       Schema::create('public_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            $table->string('title')->unique();
            $table->text('subtitle')->nullable();
            $table->string('slug')->unique();
            
            $table->string('submitted_for')->nullable(); // Ex: 'leads', 'deals', etc.
            $table->string('custom_logo')->nullable();
            $table->string('submit_button_text')->default('Submit Form');
            $table->string('model_type')->nullable(); // App\Models\Lead

            // 2. Default Lead Attributes (এগুলো সেট থাকলে ফর্ম সাবমিটের সাথে সাথে লিড অ্যাসাইন হতে সহজ হবে)
            $table->foreignId('default_status_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->foreignId('default_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->foreignId('default_subject_id')->nullable()->constrained('lead_subjects')->nullOnDelete();
            $table->foreignUuid('default_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('default_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('default_assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            
            // 3. Post Submission Actions
            $table->text('success_message')->nullable();
            $table->string('redirect_url')->nullable();
            
            // 4. Submission & Lead Creation Mode
            // 'auto_lead' = সরাসরি Lead টেবিলে যাবে
            // 'response_only' = ফর্ম রেসপন্স টেবিলে থাকবে, এডমিন ম্যানুয়ালি লিড বানাবে
            $table->string('submission_mode')->default('response_only'); 

            // Extra Settings / Customizations
            $table->json('meta')->nullable();

            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->softDeletes()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_forms');
    }
};
