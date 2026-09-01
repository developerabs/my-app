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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no', 50)->unique();
            $table->string('vendor_invoice_no', 100)->nullable();
            $table->date('bill_date')->index();
            $table->date('due_date')->index();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignUuid('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('exchange_rate', 15, 8)->default(1);
            $table->string('project_id', 50)->nullable()->index(); // Future Project Scope

            // Multi-Currency Amounts (Transaction & Base Currency)
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_base_amount', 15, 2)->default(0);

            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('base_paid_amount', 15, 2)->default(0); // Added 🎯

            $table->decimal('due_amount', 15, 2)->default(0);
            $table->decimal('base_due_amount', 15, 2)->default(0); // Added 🎯

            $table->string('payment_status', 20)->default('unpaid')->index(); // unpaid, partially_paid, paid
            $table->string('status', 20)->default('posted')->index(); // draft, posted, cancelled
            $table->string('attachment')->nullable();
            $table->text('note')->nullable();
            $table->boolean('has_late_fee')->default(false);
            $table->jsonb('late_fee_config')->nullable();

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
        Schema::dropIfExists('bills');
    }
};
