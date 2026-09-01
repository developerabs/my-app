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
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('unit_groups')->onDelete('cascade');
            $table->uuid('base_unit_id')->nullable()->index();
            
            $table->string('name');
            $table->string('short_name')->index();
            $table->string('description')->nullable();
            $table->boolean('is_base_unit')->default(false)->index();

            $table->enum('operator', ['*', '/'])->nullable()->default('*');
            $table->decimal('operator_value', 19, 6)->nullable()->default(1.000000);
            $table->boolean('is_formulaic')->default(false);
            $table->text('formula')->nullable(); 
            $table->integer('precision')->default(2);
            $table->json('display_params')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
