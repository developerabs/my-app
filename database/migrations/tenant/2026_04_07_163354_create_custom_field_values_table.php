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
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->onDelete('cascade');
            $table->bigInteger('fieldable_id'); // fieldable_id + fieldable_type
            $table->string('fieldable_type'); // fieldable_id + fieldable_type
            $table->text('value')->nullable();
            $table->timestamps();
            /* 
               Adding an index for faster lookup when filtering by custom values.
            */
            $table->index(['custom_field_id', 'fieldable_id', 'fieldable_type'], 'cf_values_main_index');
            $table->index([\DB::raw('value')], 'cf_values_search_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
