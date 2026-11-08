<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per package download. The plugin keeps textures cached, so a
        // row means a first download or a new version/resolution, not every use.
        Schema::create('texture_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('texture_id')->constrained()->cascadeOnDelete();
            $table->string('resolution', 4);
            $table->unsignedInteger('version');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('downloaded_at');
            $table->timestamps();

            $table->index(['user_id', 'texture_id']);
            $table->index(['texture_id', 'downloaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('texture_downloads');
    }
};
