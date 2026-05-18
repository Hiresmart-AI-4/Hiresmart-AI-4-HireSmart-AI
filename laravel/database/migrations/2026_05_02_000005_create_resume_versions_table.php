<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_versions', function (Blueprint $table) {
            $table->uuid('version_id')->primary();
            $table->foreignUuid('resume_id')->constrained('resumes', 'resume_id')->onDelete('cascade');
            $table->string('version_number');
            $table->string('file_url');
            $table->json('changes')->nullable();
            $table->text('notes')->nullable();
            $table->integer('ats_score')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_versions');
    }
};
