<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

Route::get('/', function () {
    return view('upload'); 
});

Route::post('/upload', [UploadController::class, 'upload']);
Route::get('/uploads', [UploadController::class, 'list']);
Route::get('/preview-text/{filename}', [UploadController::class, 'previewText']);
