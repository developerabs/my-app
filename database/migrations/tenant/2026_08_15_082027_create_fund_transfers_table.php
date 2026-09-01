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
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no')->unique();
            $table->date('transfer_date');

            $table->foreignId('from_account_id')->constrained('accounts');
            $table->foreignId('to_account_id')->constrained('accounts');
            $table->decimal('amount', 15, 2);
            $table->decimal('base_amount', 15, 2);

            $table->foreignUuid('branch_id')->constrained('branches');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate', 15, 8)->default(1);

            $table->string('payment_method', 50)->default('cash');
            $table->string('reference_no', 100)->nullable();
            $table->string('attachment')->nullable();
            $table->text('note')->nullable();

            $table->string('status', 30)->default('posted');
            $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers');

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('transfer_date', 'ft_date_idx');
            $table->index('from_account_id', 'ft_from_acc_idx');
            $table->index('to_account_id', 'ft_to_acc_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_transfers');
    }
};
