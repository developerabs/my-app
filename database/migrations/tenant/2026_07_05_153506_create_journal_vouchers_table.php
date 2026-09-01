<?php

use App\Enums\JournalVoucherStatus;
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
        Schema::create('journal_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('posting_sequence')->nullable()->index();
            $table->string('voucher_no', 50)->unique();
            $table->date('voucher_date')->index();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->onDelete('cascade');
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('branches')->onDelete('cascade')->index();
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('exchange_rate', 15, 8)->default(1);
            $table->enum('voucher_type', array_column(JournalVoucherType::cases(), 'value'))->default(JournalVoucherType::JOURNAL)->index();
            $table->enum('status', array_column(JournalVoucherStatus::cases(), 'value'))->default(JournalVoucherStatus::DRAFT)->index();
            $table->string('reference_no')->nullable()->index();
            $table->string('attachment')->nullable();
            $table->string('project_id', 50)->nullable()->index();
            $table->text('narration')->nullable();
            $table->text('reverse_reason')->nullable();
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->decimal('total_base_debit', 15, 2)->default(0);
            $table->decimal('total_base_credit', 15, 2)->default(0);

            $table->string('external_id', 100)->nullable()->index();
            $table->string('source_from')->default('web');

            $table->string('sourceable_type')->nullable();
            $table->string('sourceable_id', 36)->nullable();
            $table->foreignId('reversal_of')
                ->nullable()
                ->constrained('journal_vouchers')
                ->nullOnDelete();

            $table->foreignId('reversed_by_voucher')
                ->nullable()
                ->constrained('journal_vouchers')
                ->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();

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
        Schema::dropIfExists('journal_vouchers');
    }
};
