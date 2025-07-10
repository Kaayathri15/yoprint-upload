<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Jobs\ProcessCsvUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Handle file upload and processing.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $hash = hash_file('sha256', $file->getRealPath());

        // Check if identical file already uploaded (idempotent)
        $existing = Upload::where('file_name', $fileName)
            ->where('file_hash', $hash)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Same file already uploaded.'], 200);
        }
        $existing = Upload::where('file_name', $fileName)->latest()->first();

        if ($existing && $existing->file_hash !== $hash) {
        $path = $file->store('uploads', 'public');
            $existing->update([
                'file_path' => $path,
                'file_hash' => $hash,
                'status' => 'pending',
            ]);

            ProcessCsvUpload::dispatch($existing->id);
            return response()->json(['message' => 'File updated and reprocessed.']);
        }

        // First time uploading this file
        $path = $file->store('uploads', 'public');

        $upload = Upload::create([
            'file_name' => $fileName,
            'file_path' => $path,
            'file_hash' => $hash,
            'status' => 'pending',
        ]);

        ProcessCsvUpload::dispatch($upload->id);

        return response()->json(['message' => '✅ New file uploaded and processing started.']);
    }


    public function list()
    {
        return Upload::orderByDesc('updated_at')->get();
    }

// In UploadController.php

public function previewText($filename)
{
    $path = storage_path('app/public/uploads/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'text/plain',
        'Content-Disposition' => 'inline; filename="' . $filename . '"'
    ]);
}
}
