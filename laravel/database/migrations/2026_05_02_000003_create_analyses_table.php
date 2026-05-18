<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->uuid('analysis_id')->primary();
            $table->foreignUuid('resume_id')->constrained('resumes', 'resume_id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('skills')->nullable();
            $table->integer('total_score')->default(0);
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('missing_keywords')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
