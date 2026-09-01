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
        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('purchase_no', 50)->unique();
            $table->string('reference', 100)->nullable(); // Internal PO Reference
            $table->string('memo_number', 100)->nullable(); // Supplier Invoice / Challan / Memo No
            $table->date('purchase_date')->index();
            $table->date('due_date')->nullable()->index(); // Credit Term Due Date

            $table->foreignUuid('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->decimal('exchange_rate', 15, 8)->default(1.00000000);

            // Operational & Workflow Statuses
            $table->string('purchase_status', 30)->default('received')->index(); // ordered, pending, partial_received, received, cancelled
            $table->string('payment_status', 30)->default('unpaid')->index(); // unpaid, partially_paid, paid
            $table->string('status', 30)->default('posted')->index(); // draft, posted, cancelled

            // Quantity Summary
            $table->decimal('total_qty', 15, 4)->default(0);

            // Order Valuation Breakdown (Transaction Currency)
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->string('order_discount_method', 20)->default('flat'); // flat, percentage
            $table->decimal('order_discount_rate', 8, 2)->default(0);
            $table->decimal('order_discount_amount', 15, 2)->default(0);
            
            $table->foreignUuid('order_tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('order_tax_method', 20)->default('exclusive'); // exclusive, inclusive
            $table->decimal('order_tax_rate', 8, 2)->default(0);
            $table->decimal('order_tax_amount', 15, 2)->default(0);
            
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('other_expenses', 15, 2)->default(0); // Handling, Freight, Customs
            $table->decimal('round_off', 8, 2)->default(0); // Decimal Rounding Adjustment (-0.99 to +0.99)

            // Grand Totals & Balances (Transaction Currency)
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('due_amount', 15, 2)->default(0);

            // Base Currency Values (For Multi-Currency General Ledger Integrity)
            $table->decimal('total_base_amount', 15, 2)->default(0);
            $table->decimal('base_subtotal_amount', 15, 2)->default(0);
            $table->decimal('base_order_discount_amount', 15, 2)->default(0);
            $table->decimal('base_order_tax_amount', 15, 2)->default(0);
            $table->decimal('base_shipping_cost', 15, 2)->default(0);
            $table->decimal('base_other_expenses', 15, 2)->default(0);
            $table->decimal('base_paid_amount', 15, 2)->default(0);
            $table->decimal('base_due_amount', 15, 2)->default(0);

            // General Ledger Integration
            $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers')->nullOnDelete();
            $table->string('project_id', 50)->nullable()->index(); // Future Project Scope
            $table->string('document')->nullable(); // Bill Attachment Path (S3)
            $table->text('note')->nullable();
            $table->boolean('has_late_fee')->default(false);
            $table->jsonb('late_fee_config')->nullable();

            // Auditing Columns
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes()->index();

            // Optimized High-Performance Composite Indexes
            $table->index(['supplier_id', 'purchase_status'], 'idx_pur_supplier_status');
            $table->index(['branch_id', 'purchase_date'], 'idx_pur_branch_date');
            $table->index(['status', 'purchase_date'], 'idx_pur_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
