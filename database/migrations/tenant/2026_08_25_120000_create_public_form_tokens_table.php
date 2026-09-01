<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_form_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_form_id')->constrained('public_forms')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->text('token_encrypted')->nullable();
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['public_form_id', 'is_used', 'expires_at'], 'public_form_token_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_form_tokens');
    }
};