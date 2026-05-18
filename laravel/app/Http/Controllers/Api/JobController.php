<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobMatch;
use App\Models\Resume;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class JobController extends Controller
{
    public function __construct(private AIService $aiService)
    {
    }

    public function index()
    {
        return response()->json(
            Job::where('is_active', true)->orderByDesc('created_at')->get()
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'required_skills' => ['required', 'array'],
            'nice_to_have_skills' => ['nullable', 'array'],
            'employment_type' => ['required', 'string', 'max:255'],
            'experience_level' => ['required', 'string', 'max:255'],
            'salary_min' => ['nullable', 'numeric'],
            'salary_max' => ['nullable', 'numeric'],
            'application_deadline' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $job = Job::create(array_merge($validator->validated(), [
            'job_id' => (string) Str::uuid(),
            'recruiter_id' => $request->user()->id,
        ]));

        return response()->json([
            'message' => 'Job created successfully',
            'job' => $job,
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json(Job::with('matches')->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $job = Job::findOrFail($id);

        if ((int) $job->recruiter_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'company' => ['sometimes', 'required', 'string', 'max:255'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'required_skills' => ['sometimes', 'required', 'array'],
            'nice_to_have_skills' => ['nullable', 'array'],
            'employment_type' => ['sometimes', 'required', 'string', 'max:255'],
            'experience_level' => ['sometimes', 'required', 'string', 'max:255'],
            'salary_min' => ['nullable', 'numeric'],
            'salary_max' => ['nullable', 'numeric'],
            'application_deadline' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $job->update($validator->validated());

        return response()->json([
            'message' => 'Job updated successfully',
            'job' => $job->fresh(),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $job = Job::findOrFail($id);

        if ((int) $job->recruiter_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $job->delete();

        return response()->json(['message' => 'Job deleted successfully']);
    }

    public function match(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'resume_id' => ['required', 'exists:resumes,resume_id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $job = Job::findOrFail($id);
        $resume = Resume::findOrFail($request->resume_id);

        if ((int) $resume->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $matchResult = $this->aiService->matchResumeToJob($resume->parsed_content ?? '', [
            'required_skills' => $job->required_skills ?? [],
        ]);

        $match = JobMatch::firstOrNew([
            'user_id' => $request->user()->id,
            'job_id' => $job->job_id,
            'resume_id' => $resume->resume_id,
        ]);

        if (! $match->exists) {
            $match->match_id = (string) Str::uuid();
        }

        $match->fill([
            'match_score' => $matchResult['match_score'],
            'skill_match' => $matchResult['matching_skills'],
            'missing_skills' => $matchResult['missing_skills'],
            'recommendations' => $matchResult['recommendations'],
        ]);
        $match->save();

        return response()->json([
            'message' => 'Job match generated',
            'match' => $match,
        ]);
    }
}
