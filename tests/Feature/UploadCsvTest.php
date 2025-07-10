<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Jobs\ProcessCsvUpload;
use App\Models\Upload;

class UploadCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_a_csv_and_dispatch_job()
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->createWithContent(
            'products.csv',
            "name,sku,description,price\nTee,SKU001,Basic Tee,9.99"
        );

        $response = $this->post('/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(200);

        // ✅ Match your controller's storeAs() path
        $this->assertTrue(count(Storage::disk('public')->files('uploads')) > 0);

        // ✅ Check DB record
        $this->assertDatabaseHas('uploads', [
            'file_name' => 'products.csv',
        ]);

        // ✅ Job was dispatched
        Queue::assertPushed(ProcessCsvUpload::class);
    }

    /** @test */
    public function test_rejects_non_csv_files()
    {
        $file = UploadedFile::fake()->create('not-a-csv.pdf', 100);

        $response = $this->post('/upload', [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors(['file']);
    }
}
