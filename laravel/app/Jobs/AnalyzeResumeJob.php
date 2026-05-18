<?php

namespace App\Jobs;

use App\Models\Resume;
use App\Services\AnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeResumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Resume $resume)
    {
    }

    public function handle(AnalysisService $analysisService): void
    {
        $analysisService->analyzeResume($this->resume);
    }
}
