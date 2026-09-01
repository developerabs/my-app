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
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('address_type')->default('home')->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->text('full_address')->nullable();

            $table->string('state')->nullable()->index();
            $table->string('city')->nullable()->index();
            $table->string('post_code')->nullable()->index();

            $table->string('upazila')->nullable()->index();  
            $table->string('district')->nullable()->index();
            $table->string('division')->nullable()->index();
            $table->string('country')->nullable()->index();

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
