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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('tenant_id')->nullable();
            $table->double('base_amount')->default(0);
            $table->string('base_currency')->default('BDT');
            $table->double('pay_amount')->default(0);
            $table->string('pay_currency')->nullable()->default('BDT');
            $table->double('exchange_rate')->nullable()->default(1);
            $table->string('payment_method')->nullable();
            $table->string('gateway')->nullable();
            $table->string('payment_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('credential_owner')->nullable()->comment('Indicates whether the credentials used were landlord or tenant');
            $table->string('paid_for')->nullable();
            $table->string('paid_by')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
