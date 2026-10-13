<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();

            // Tag normalized to lowercase without accents for consistent search
            // e.g. "Porta Pivotante" → "porta pivotante"
            $table->string('tag', 80);

            $table->timestamps();

            // Prevents duplicate tags on the same family
            $table->unique(['family_id', 'tag']);

            // Index for tag lookups (autocomplete, suggestions, etc.)
            $table->index('tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_tags');
    }
};
