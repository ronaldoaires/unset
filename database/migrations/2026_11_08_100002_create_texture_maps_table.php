<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('texture_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('texture_id')->constrained()->cascadeOnDelete();

            // diffuse | normal | roughness | ao | displacement | alpha | metallic
            $table->string('type', 20);

            // 1k | 2k | 4k | 8k. Each texture may ship one set of maps per
            // resolution; a missing map falls back to the next lower resolution.
            $table->string('resolution', 4)->default('1k');

            // Maps are the product: private disk, served only through the
            // authorized download.
            $table->string('disk', 20)->default('local');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('width_px')->nullable();
            $table->unsignedInteger('height_px')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('mime_type', 100)->nullable();

            $table->timestamps();

            $table->unique(['texture_id', 'type', 'resolution']);
            $table->index(['texture_id', 'resolution']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('texture_maps');
    }
};
