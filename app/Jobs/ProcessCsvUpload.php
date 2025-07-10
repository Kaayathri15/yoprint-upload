<?php

namespace App\Jobs;

use App\Models\Upload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uploadId;

    /**
     * Create a new job instance.
     */
    public function __construct($uploadId)
    {
        $this->uploadId = $uploadId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $upload = Upload::find($this->uploadId);

        if (!$upload) {
            Log::warning("Upload record not found for ID: " . $this->uploadId);
            return;
        }

        // ✅ Mark as processing
        $upload->update(['status' => 'processing']);

        try {
            // ✅ Get file contents (you can replace this with actual CSV processing)
            $filePath = $upload->file_path; // this is relative to 'public' disk
            $fullPath = Storage::disk('public')->path($filePath);

            // For demonstration: read and log first few lines
            $lines = file($fullPath);
            foreach (array_slice($lines, 0, 5) as $line) {
                Log::info("CSV Line: " . trim($line));
            }

            // ✅ Mark as completed
            $upload->update(['status' => 'completed']);

        } catch (\Exception $e) {
            // ❌ On error, mark as failed and log the issue
            Log::error("Failed to process CSV upload [ID: {$this->uploadId}]: " . $e->getMessage());
            $upload->update(['status' => 'failed']);
        }
    }
}
