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
        Schema::create('public_form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_form_id')->constrained('public_forms')->cascadeOnDelete();
            $table->json('response_data'); // ইউজারের সাবমিট করা সব ডাটা JSON আকারে থাকবে
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Extra Settings / Customizations
            $table->json('meta')->nullable();

            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            
            // লিড তৈরি হলে তার আইডি রেফারেন্স (Optional)
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete(); 
            
            $table->softDeletes()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_form_responses');
    }
};
