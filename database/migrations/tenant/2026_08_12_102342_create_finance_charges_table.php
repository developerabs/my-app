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
        Schema::create('finance_charges', function (Blueprint $table) {
            $table->id();
            $table->string('charge_no')->unique();
            $table->date('charge_date');

            $table->string('chargeable_type');
            $table->string('chargeable_id', 50);

            $table->integer('days_overdue')->default(0);
            $table->enum('fee_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('rate', 8, 2)->default(0); // e.g. 3.00% or 200.00 BDT
            $table->decimal('amount', 15, 2); // Transaction Currency Amount
            $table->decimal('base_amount', 15, 2); // Base Currency Amount
            $table->string('status', 30)->default('posted'); // posted, partially_waived, waived, cancelled

            $table->foreignUuid('branch_id')->constrained('branches');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate', 15, 8)->default(1);

            $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers');
            $table->text('note')->nullable();

            $table->timestamp('waived_at')->nullable();
            $table->foreignId('waived_by')->nullable()->constrained('users');

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['chargeable_type', 'chargeable_id'], 'fc_chargeable_composite_idx');
            $table->index('charge_date', 'fc_charge_date_idx');
            $table->index('status', 'fc_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_charges');
    }
};
