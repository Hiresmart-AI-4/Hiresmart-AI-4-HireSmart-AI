<?php

namespace App\Services;

class AIService
{
    public function analyzeResume(string $resumeContent, ?string $jobDescription = null): array
    {
        $keywords = $jobDescription
            ? $this->extractKeywords($jobDescription)
            : ['leadership', 'project management', 'communication', 'problem solving'];

        $foundKeywords = array_filter($keywords, fn ($keyword) => stripos($resumeContent, $keyword) !== false);
        $missingKeywords = array_values(array_diff($keywords, $foundKeywords));
        $baseScore = min(95, 65 + (count($foundKeywords) * 8));

        return [
            'ats_score' => max(55, $baseScore),
            'strengths' => [
                'Clear professional profile',
                'Relevant skills are easy to scan',
                'Readable resume structure',
            ],
            'weaknesses' => [
                'Could include more measurable achievements',
                'Could include more job-specific keywords',
            ],
            'missing_keywords' => $missingKeywords ?: ['No major missing keywords detected'],
            'feedback' => 'Your resume is ready for baseline screening. Improve it further by adding measurable outcomes and matching more job keywords.',
        ];
    }

    public function matchResumeToJob(string $resumeContent, array $jobRequirements): array
    {
        $requiredSkills = $jobRequirements['required_skills'] ?? [];
        $resumeSkills = $this->extractSkills($resumeContent);

        $matchingSkills = array_values(array_intersect($resumeSkills, $requiredSkills));
        $missingSkills = array_values(array_diff($requiredSkills, $resumeSkills));
        $matchScore = count($requiredSkills) > 0
            ? (int) round((count($matchingSkills) / count($requiredSkills)) * 100)
            : 0;

        return [
            'match_score' => $matchScore,
            'matching_skills' => $matchingSkills,
            'missing_skills' => $missingSkills,
            'recommendations' => $this->generateRecommendations($missingSkills),
        ];
    }

    private function extractSkills(string $content): array
    {
        $commonSkills = [
            'PHP',
            'Laravel',
            'Python',
            'JavaScript',
            'React',
            'Vue.js',
            'MySQL',
            'PostgreSQL',
            'AWS',
            'Docker',
            'Git',
            'REST API',
            'Agile',
        ];

        return array_values(array_filter(
            $commonSkills,
            fn ($skill) => stripos($content, $skill) !== false
        ));
    }

    private function extractKeywords(string $content): array
    {
        $words = preg_split('/[^a-zA-Z0-9+#.]+/', strtolower($content), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, fn ($word) => strlen($word) > 3);

        return array_values(array_slice(array_unique($words), 0, 12));
    }

    private function generateRecommendations(array $missingSkills): array
    {
        return array_map(
            fn ($skill) => "Consider adding {$skill} to your skills section or project examples.",
            $missingSkills
        );
    }
}
