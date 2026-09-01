<?php

use App\Enums\AssetEntryType;
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
        Schema::create('asset_registers', function (Blueprint $table) {
            $table->id();
            $table->string('register_no')->unique();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->decimal('exchange_rate', 15, 8)->default(1.00000000);
            
            $table->enum('entry_type', array_column(AssetEntryType::cases(), 'value'))->default(AssetEntryType::OPENING->value);
            $table->date('register_date')->index();
            
            $table->decimal('total_cost', 18, 4)->default(0);
            $table->decimal('base_total_cost', 18, 4)->default(0);
            
            // General Ledger Journal Voucher Link (Used only for opening equity vouchers & auto-reversals)
            $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers')->nullOnDelete();
            
            $table->text('remarks')->nullable();
            
            // Audit Trails
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes()->index();

            $table->index('branch_id');
            $table->index('entry_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_registers');
    }
};
