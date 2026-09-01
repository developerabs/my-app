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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_voucher_id')->constrained('journal_vouchers')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->string('sub_ledger_type')->nullable();
            $table->string('sub_ledger_id', 50)->nullable();
            $table->unsignedInteger('line_no');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('base_debit', 15, 2)->default(0);
            $table->decimal('base_credit', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('project_id', 50)->nullable()->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['journal_voucher_id', 'line_no']);
            $table->index(['sub_ledger_type', 'sub_ledger_id']);
            $table->index(['account_id', 'sub_ledger_type', 'sub_ledger_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
