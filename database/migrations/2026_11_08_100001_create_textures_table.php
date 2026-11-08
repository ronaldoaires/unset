<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('textures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('texture_category_id')->constrained()->restrictOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // pbr | glass | metal | water | mirror | emissive. Only "pbr" needs
            // image maps; the others are built from a Revit library asset plus
            // base_color.
            $table->string('material_type', 20)->default('pbr');
            $table->string('base_color', 7)->nullable()
                ->comment('Hex color for non-image materials or as a tint');

            // Real-world size of one tile of the image, independent of the
            // resolution: a 1k and an 8k map of the same texture share it.
            $table->unsignedInteger('tile_width_mm')->default(1000);
            $table->unsignedInteger('tile_height_mm')->default(1000);
            $table->boolean('is_seamless')->default(true);

            // Average color of the diffuse map, used by the plugin as the
            // material's shaded-view color. Computed server-side.
            $table->string('shading_color', 7)->nullable();

            // Public cover (webp + thumb on the "public" disk). Generated from
            // the diffuse map when the admin doesn't upload one.
            $table->string('preview_path')->nullable();

            // Bumped every time the maps change, so clients know their cached
            // copy is outdated.
            $table->unsignedInteger('version')->default(1);

            // Access: free for every signed-in user, otherwise any active plan.
            $table->boolean('is_free')->default(true);
            $table->boolean('is_featured')->default(false);

            // Approval workflow, same rules as families:
            // draft | pending_review | published | rejected
            $table->string('status', 20)->default('draft');
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->unsignedInteger('download_count')->default(0);

            $table->timestamps();

            $table->index(['status', 'texture_category_id', 'is_free'], 'textures_main_filter');
            $table->index(['status', 'is_featured']);
            $table->index(['user_id', 'status']);
            $table->index('material_type');
            $table->index('download_count');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('textures');
    }
};
