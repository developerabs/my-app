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
        Schema::create('meetings', function (Blueprint $table) {
             $table->id();

            // Polymorphic Relation
            $table->morphs('meetingable');

            // Meeting Information
            $table->string('title');
            $table->text('description')->nullable();

            // Meeting Date & Time
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();

            // Meeting Type
            $table->string('type');

            // Meeting Location / Online Link
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();

            $table->foreignUuid('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            // Status
            $table->foreignId('status_id')
                ->nullable()
                ->constrained('statuses')
                ->nullOnDelete();

            // Reminder
            $table->dateTime('reminder_at')->nullable();
            $table->boolean('reminder_sent')->default(false);

            // Assigned / Responsible Person
            $table->foreignId('assigned_to_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('is_completed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('completion_notes')->nullable();
            
            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes()->index();
            $table->timestamps();

            $table->index(['start_at', 'status_id']);
            $table->index(['created_by', 'start_at'], 'meetings_created_by_start_at_index');
            $table->index(['assigned_to_id', 'start_at'], 'meetings_assigned_to_start_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
