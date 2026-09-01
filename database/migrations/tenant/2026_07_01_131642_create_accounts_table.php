<?php

use App\Enums\LedgerAccountType;
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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->string('account_name', 150);
            $table->string('account_code', 50)->unique();

            $table->enum('account_type', array_column(LedgerAccountType::cases(), 'value'))->default(LedgerAccountType::OTHER)->index();

            $table->string('account_number', 50)->nullable()->index();
            $table->string('bank_name', 100)->nullable();
            $table->string('branch_name', 100)->nullable();
            $table->string('routing_number', 20)->nullable();
            $table->json('bank_details')->nullable();

            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('base_opening_balance', 15, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('base_current_balance', 15, 2)->default(0);
            $table->timestamp('last_transaction_date')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false)->index();
            $table->boolean('is_default')->default(false)->index();

            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();

            // English: Integration and Source tracking
            $table->string('external_id', 100)->nullable()->index();
            $table->string('source_from')->default('web'); // e.g., 'web', 'api', 'excel'

            

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('chart_of_account_id');
            $table->index(['account_type', 'is_active']);
            $table->index('currency_id');
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
