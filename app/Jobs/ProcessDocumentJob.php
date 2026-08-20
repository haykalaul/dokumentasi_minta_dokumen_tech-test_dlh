<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Document $document) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("🤖 [Queue Job] Starting background processing for Document ID: {$this->document->id}");
        Log::info("📄 [Queue Job] Original name: {$this->document->original_name}, size: {$this->document->file_size} bytes");

        // Simulate background scanning/hashing processing
        $simulatedScanSuccess = true;

        if ($simulatedScanSuccess) {
            Log::info("✅ [Queue Job] Background processing completed successfully for Document ID: {$this->document->id}");
        } else {
            Log::error("❌ [Queue Job] Background processing failed for Document ID: {$this->document->id}");
        }
    }
}
