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
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no', 50)->unique();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->date('payment_date')->index();
            $table->foreignId('payment_account_id')->constrained('accounts')->onDelete('restrict');
            $table->foreignUuid('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('exchange_rate', 15, 8)->default(1);
            $table->string('payment_method', 50)->default('cash');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->string('reference_no', 100)->nullable();
            $table->string('attachment')->nullable();
            $table->text('note')->nullable();
            
            // Polymorphic link to Bill / Purchase / Supplier
            $table->string('payable_type')->nullable();
            $table->string('payable_id', 50)->nullable();
            
            $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['payable_type', 'payable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
