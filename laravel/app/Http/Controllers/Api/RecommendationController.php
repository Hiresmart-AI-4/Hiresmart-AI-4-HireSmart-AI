<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __construct(private RecommendationService $recommendationService)
    {
    }

    public function resume(Request $request, string $resumeId)
    {
        $resume = Resume::findOrFail($resumeId);

        if ((int) $resume->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'recommendations' => $this->recommendationService->generateResumeRecommendations($resume),
        ]);
    }
}
