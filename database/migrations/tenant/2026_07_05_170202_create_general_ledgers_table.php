<?php

use App\Enums\GeneralLedgerStatus;
use App\Enums\JournalVoucherType;
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
        Schema::create('general_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('posting_sequence')->nullable()->index();
            $table->foreignId('journal_voucher_id')->constrained()->restrictOnDelete();
            $table->foreignId('journal_entry_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->onDelete('cascade');
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('exchange_rate', 15, 8)->default(1);
            $table->date('transaction_date')->index();

            $table->string('sub_ledger_type')->nullable();
            $table->string('sub_ledger_id', 50)->nullable();

            $table->string('voucher_no', 50);
            $table->enum('voucher_type', array_column(JournalVoucherType::cases(), 'value'));
            $table->unsignedInteger('line_no');
            $table->string('reference_no', 100)->nullable();
            $table->string('narration')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('base_debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('base_credit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('base_balance', 15, 2)->default(0);

            $table->enum('status', array_column(GeneralLedgerStatus::cases(), 'value'))->default(GeneralLedgerStatus::POSTED);

            $table->string('sourceable_type')->nullable();
            $table->string('sourceable_id', 36)->nullable();

            $table->boolean('is_opening')->default(false)->index();
            $table->boolean('is_system_generated')->default(false)->index();
            $table->string('project_id', 50)->nullable()->index();

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();

            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique('journal_entry_id');
            $table->index(['account_id', 'transaction_date']);
            $table->index(['account_id', 'branch_id', 'transaction_date']);
            $table->index(['account_id', 'sub_ledger_type', 'sub_ledger_id', 'transaction_date']);
            $table->index(['sub_ledger_type', 'sub_ledger_id']);
            $table->index(['voucher_type', 'transaction_date']);
            $table->index(['voucher_no', 'line_no']);
            $table->index(['fiscal_year_id', 'accounting_period_id']);
            $table->index(['status', 'transaction_date']);
            $table->index('reference_no');
            $table->index(['sourceable_type', 'sourceable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_ledgers');
    }
};
