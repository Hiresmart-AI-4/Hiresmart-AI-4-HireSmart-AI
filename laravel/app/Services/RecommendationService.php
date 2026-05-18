<?php

namespace App\Services;

use App\Models\Resume;

class RecommendationService
{
    public function generateResumeRecommendations(Resume $resume): array
    {
        $skills = $resume->parsed_data['skills'] ?? [];
        $recommendations = [
            'Add quantified achievements to each recent role.',
            'Mirror important keywords from the target job description.',
            'Keep formatting simple for ATS parsing.',
        ];

        if (! in_array('Git', $skills, true)) {
            $recommendations[] = 'Add Git if it is part of your workflow.';
        }

        return $recommendations;
    }
}
