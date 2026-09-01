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
        Schema::create('trashes', function (Blueprint $table) {
            $table->id();
            // PostgreSQL friendly polymorphic fields for UUID/String IDs
            $table->string('trashable_type');
            $table->string('trashable_id'); // String use করা হয়েছে যাতে UUID এবং Normal ID দুইটাই সাপোর্ট করে
            $table->index(['trashable_type', 'trashable_id']); // Indexing for faster lookups
            
            $table->string('name')->nullable();
            
            // Use foreignId with constrained for Postgres integrity
            $table->foreignId('deleted_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
                  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trashes');
    }
};
