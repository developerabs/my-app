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
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('tenant_id', 100)->nullable();
            $table->string('paymentID', 100)->nullable();
            $table->string('trxID', 100)->nullable();
            $table->string('transactionStatus', 100)->nullable();
            $table->double('amount', 100)->default(0);
            $table->string('currency', 100)->nullable();
            $table->string('intent', 100)->nullable();
            $table->string('paymentExecuteTime', 100)->nullable();
            $table->string('merchantInvoiceNumber', 100)->nullable();
            $table->string('payerType', 100)->nullable();
            $table->string('payerReference', 100)->nullable();
            $table->string('customerMsisdn', 100)->nullable();
            $table->string('payerAccount', 100)->nullable();
            $table->string('statusCode', 100)->nullable();
            $table->string('statusMessage', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_details');
    }
};
