<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();

            /*
             * Possible types:
             *   rfa   — main Revit file (.rfa)
             *   image — presentation image
             *   thumb — auto-generated thumbnail
             *
             * NOTE: no 3D preview/model type here on purpose — this rebuild
             * does not handle 3D model previews at all.
             */
            $table->string('type', 20);

            // Storage
            // NOTE: defaults to 'public' assuming the same shared-hosting setup
            // (local disk + StorageController) used on the other projects.
            // Change to 's3' here if this project will use object storage instead.
            $table->string('disk', 20)->default('public')
                ->comment('Laravel Storage driver: public | s3 | etc.');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('mime_type', 100)->nullable();

            // Which file is the primary one for each type
            $table->boolean('is_primary')->default(false)
                ->comment('Main file of this type for the family');

            $table->timestamps();

            $table->index(['family_id', 'type']);
            $table->index(['family_id', 'type', 'is_primary'], 'family_files_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_files');
    }
};
