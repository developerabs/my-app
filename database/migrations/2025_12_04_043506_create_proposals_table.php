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
        Schema::create('proposals', function (Blueprint $table) {
          $table->id();
            $table->unsignedBigInteger('reseller_id')->nullable();
            $table->string('tenant_id')->nullable();
            $table->string('proposal_number');
            $table->string('company_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_address')->nullable();
            $table->text('proposal_details')->nullable();
            $table->string('package');
            $table->decimal('registration_fee', 10, 2)->default(10000);
            $table->decimal('discount', 10, 2)->default(10000);
            $table->enum('discount_type', ['flat', 'percentage'])->default('flat');
            $table->decimal('subscription_fee', 10, 2)->default(500);
            $table->decimal('monthly', 10, 2)->default(500);
            $table->decimal('yearly', 10, 2)->default(5000);
            $table->decimal('lifetime', 10, 2)->default(5000);
            $table->integer('validity')->default(30);
            $table->string('demo_link')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->text('special_note')->nullable();
            $table->integer('added_by')->nullable();
            $table->enum('status', ['pending', 'sent', 'rejected', 'completed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
