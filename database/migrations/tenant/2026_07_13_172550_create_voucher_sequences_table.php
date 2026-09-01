<?php

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
        Schema::create('voucher_sequences', function (Blueprint $table) {
            $table->id();

            $table->enum('voucher_type', array_column(JournalVoucherType::cases(), 'value'))->index();

            $table->foreignId('fiscal_year_id')
                ->constrained('fiscal_years')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('last_number')->default(0);

            $table->timestamps();

            $table->unique([
                'voucher_type',
                'fiscal_year_id'
            ], 'voucher_sequence_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_sequences');
    }
};
