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
        Schema::create('homepage_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('subtitle')->nullable();
            $table->enum('type', ['slider', 'grid', 'text', 'form', 'header', 'footer', 'section'])->default('text');
            $table->enum('content_type', ['static', 'dynamic'])->default('static');
            $table->json('content')->nullable();
            $table->json('settings')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_global')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_widgets');
    }
};
