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
        Schema::create('unions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('upazilla_id'); // English: Fixed naming as per convention (was upazilla_id)
            $table->string('name', 25);
            $table->string('bn_name', 25);
            $table->string('url', 50);
            $table->timestamps();

            // English: Foreign key relationship with upazilas table
            $table->foreign('upazilla_id')->references('id')->on('upazilas')->onDelete('cascade');
            
            $table->index('upazilla_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unions');
    }
};
