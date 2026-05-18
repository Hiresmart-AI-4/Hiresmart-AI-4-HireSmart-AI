<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_matches', function (Blueprint $table) {
            $table->uuid('match_id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('job_id')->constrained('job_posts', 'job_id')->onDelete('cascade');
            $table->foreignUuid('resume_id')->constrained('resumes', 'resume_id')->onDelete('cascade');
            $table->integer('match_score');
            $table->json('skill_match')->nullable();
            $table->json('missing_skills')->nullable();
            $table->json('recommendations')->nullable();
            $table->boolean('is_viewed')->default(false);
            $table->boolean('is_applied')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_matches');
    }
};
