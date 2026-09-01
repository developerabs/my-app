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
        Schema::create('reseller_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_id')->default(0);
            $table->string('tenant_id')->nullable();
            $table->string('domain')->nullable();
            $table->integer('package_id')->nullable();
            $table->double('registration_fee')->default(0);
            $table->integer('commission')->default(0);
            $table->double('comission_amount')->default(0);
            $table->double('due')->default(0);
            $table->double('paid')->default(0);
            $table->double('admin_receivable')->default(0);
            $table->double('admin_due')->default(0);
            $table->boolean('is_overdue')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_clients');
    }
};
