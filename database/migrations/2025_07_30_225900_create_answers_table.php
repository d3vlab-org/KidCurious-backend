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
        Schema::create('answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('question_id');
            $table->text('answer_text');
            $table->text('raw_answer_text');
            $table->string('moderation_status')->default('pending_moderation');
            $table->text('moderation_reason')->nullable();
            $table->json('moderation_flags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->timestamp('moderated_at')->nullable();

            // Indexes
            $table->index('question_id');
            $table->index('moderation_status');
            $table->index('created_at');
            $table->index('moderated_at');
            $table->index(['moderation_status', 'created_at']);

            // Foreign key constraint
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
