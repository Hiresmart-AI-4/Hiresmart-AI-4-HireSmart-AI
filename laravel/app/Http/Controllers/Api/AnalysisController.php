<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Analysis;
use App\Models\Resume;
use App\Services\AnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnalysisController extends Controller
{
    public function __construct(private AnalysisService $analysisService)
    {
    }

    public function analyze(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resume_id' => ['required', 'exists:resumes,resume_id'],
            'job_description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resume = Resume::findOrFail($request->resume_id);

        if ((int) $resume->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $analysis = $this->analysisService->analyzeResume($resume, $request->job_description);

        return response()->json([
            'message' => 'Analysis complete',
            'analysis' => $analysis,
            'recommendations' => $analysis->missing_keywords,
        ]);
    }

    public function dashboard(Request $request)
    {
        $resumes = Resume::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get([
                'resume_id',
                'user_id',
                'title',
                'original_filename',
                'ats_score',
                'created_at',
            ]);

        return response()->json([
            'total_resumes' => $resumes->count(),
            'average_score' => round((float) $resumes->avg('ats_score'), 2),
            'latest_resume' => $resumes->first(),
            'resumes' => $resumes,
            'score_trend' => $resumes->map(fn (Resume $resume) => [
                'date' => $resume->created_at?->format('Y-m-d') ?? '',
                'score' => $resume->ats_score ?? 0,
                'title' => $resume->title,
            ])->values(),
        ]);
    }

    public function show(Request $request, string $resumeId)
    {
        $analysis = Analysis::where('resume_id', $resumeId)->latest()->firstOrFail();

        if ((int) $analysis->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($analysis);
    }
}
