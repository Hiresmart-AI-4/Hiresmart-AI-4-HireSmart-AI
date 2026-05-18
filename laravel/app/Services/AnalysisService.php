<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\Resume;
use Illuminate\Support\Str;

class AnalysisService
{
    public function __construct(private AIService $aiService)
    {
    }

    public function analyzeResume(Resume $resume, ?string $jobDescription = null): Analysis
    {
        $result = $this->aiService->analyzeResume($resume->parsed_content ?? '', $jobDescription);

        $analysis = Analysis::create([
            'analysis_id' => (string) Str::uuid(),
            'resume_id' => $resume->resume_id,
            'user_id' => $resume->user_id,
            'skills' => $resume->parsed_data['skills'] ?? [],
            'total_score' => $result['ats_score'],
            'strengths' => $result['strengths'],
            'weaknesses' => $result['weaknesses'],
            'missing_keywords' => $result['missing_keywords'],
            'summary' => $result['feedback'],
        ]);

        $resume->update(['ats_score' => $result['ats_score']]);

        return $analysis;
    }

    public function getDetailedAnalysis(string $resumeId): Analysis
    {
        return Analysis::where('resume_id', $resumeId)->latest()->firstOrFail();
    }

    public function compareVersions(Resume $original, Resume $improved): array
    {
        return [
            'original_score' => $original->ats_score ?? 0,
            'improved_score' => $improved->ats_score ?? 0,
            'score_increase' => ($improved->ats_score ?? 0) - ($original->ats_score ?? 0),
            'suggestions_applied' => $improved->parsed_data ?? [],
        ];
    }
}
