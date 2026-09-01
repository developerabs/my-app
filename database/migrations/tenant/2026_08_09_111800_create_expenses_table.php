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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_no', 50)->unique();
            $table->date('expense_date')->index();
            $table->foreignUuid('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('exchange_rate', 15, 8)->default(1);
            $table->foreignId('payment_account_id')->constrained('accounts')->onDelete('restrict');
            $table->string('payment_method', 50)->default('cash'); // cash, bank_transfer, cheque, mfs
            $table->string('reference_no', 100)->nullable();
            $table->string('attachment')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_base_amount', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->string('status', 20)->default('posted')->index(); // draft, posted, cancelled
            $table->string('project_id', 50)->nullable()->index();
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            
            $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers')->nullOnDelete();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
