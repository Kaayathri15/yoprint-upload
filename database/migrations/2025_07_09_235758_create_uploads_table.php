<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up()
{
    Schema::create('uploads', function (Blueprint $table) {
        $table->id();
        $table->string('file_name');
        $table->string('file_path');
        $table->string('file_hash')->after('file_path')->nullable()->index();
        $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
