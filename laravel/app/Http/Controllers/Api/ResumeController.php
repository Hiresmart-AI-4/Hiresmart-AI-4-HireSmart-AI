<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Models\ResumeVersion;
use App\Services\ResumeParsingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ResumeController extends Controller
{
    public function __construct(private ResumeParsingService $resumeParsingService)
    {
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resume' => [
                'required',
                'file',
                'extensions:pdf,docx',
                'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/octet-stream',
                'max:5120',
            ],
            'title' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('resume');
        $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('resumes', $fileName, 'public');
        $fileUrl = Storage::url($filePath);
        $parsedData = $this->resumeParsingService->parse($file);

        $resume = Resume::create([
            'resume_id' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'file_url' => $fileUrl,
            'original_filename' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'parsed_content' => $parsedData['raw_content'],
            'parsed_data' => $parsedData['structured_data'],
            'is_active' => true,
            'version' => '1.0',
        ]);

        ResumeVersion::create([
            'version_id' => (string) Str::uuid(),
            'resume_id' => $resume->resume_id,
            'version_number' => '1.0',
            'file_url' => $fileUrl,
            'notes' => 'Original upload',
        ]);

        return response()->json([
            'message' => 'Resume uploaded successfully',
            'resume' => $resume->load('versions'),
        ], 201);
    }

    public function index(Request $request)
    {
        $resumes = Resume::where('user_id', $request->user()->id)
            ->with('analysis')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($resumes);
    }

    public function show(Request $request, string $id)
    {
        $resume = Resume::findOrFail($id);

        if ((int) $resume->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $resume->load('analysis');
        } catch (\Throwable $e) {
            // Silently ignore if table is missing
        }

        try {
            $resume->load('versions');
        } catch (\Throwable $e) {
            // Silently ignore if table is missing
        }

        return response()->json($resume);
    }

    public function update(Request $request, string $id)
    {
        $resume = Resume::findOrFail($id);

        if ((int) $resume->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resume->update($validator->validated());

        return response()->json([
            'message' => 'Resume updated successfully',
            'resume' => $resume->fresh(),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $resume = Resume::findOrFail($id);

        if ((int) $resume->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $path = str_replace('/storage/', '', $resume->file_url);
        Storage::disk('public')->delete($path);
        $resume->delete();

        return response()->json(['message' => 'Resume deleted successfully']);
    }

    public function activate(Request $request, string $id)
    {
        $resume = Resume::findOrFail($id);

        if ((int) $resume->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Resume::where('user_id', $request->user()->id)->update(['is_active' => false]);
        $resume->update(['is_active' => true]);

        return response()->json([
            'message' => 'Resume activated successfully',
            'resume' => $resume->fresh(),
        ]);
    }

    public function download(Request $request, string $id)
    {
        $resume = Resume::findOrFail($id);

        if ((int) $resume->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['download_url' => $resume->file_url]);
    }

    public function compare(Request $request, string $originalId, string $improvedId)
    {
        $original = Resume::findOrFail($originalId);
        $improved = Resume::findOrFail($improvedId);

        if ((int) $original->user_id !== (int) $request->user()->id || (int) $improved->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'original_score' => $original->ats_score ?? 0,
            'improved_score' => $improved->ats_score ?? 0,
            'score_increase' => ($improved->ats_score ?? 0) - ($original->ats_score ?? 0),
            'suggestions_applied' => $improved->parsed_data ?? [],
        ]);
    }
}
