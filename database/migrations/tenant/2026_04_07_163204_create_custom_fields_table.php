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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->index();

            $table->string('label');
            $table->string('name')->index();

            $table->string('type');
            $table->json('options')->nullable(); // For select/radio/checkbox options
            $table->string('default_value')->nullable();
            $table->string('placeholder')->nullable();

            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0); // To sort fields in the UI
            $table->boolean('show_in_list')->default(false); // Should it appear in DataTables?
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['model_type', 'name']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
