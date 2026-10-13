<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            // Identification
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Revit-specific attributes
            // NOTE: snake_case to match Eloquent $fillable/casts (source of a real bug
            // in the old project, where the migration used camelCase and mass
            // assignment silently failed).
            $table->unsignedSmallInteger('revit_version');
            $table->string('revit_category')->nullable();
            $table->boolean('is_parametric')->default(false)
                ->comment('Whether the family has editable parameters in Revit');

            // Pricing
            $table->boolean('is_free')->default(true);
            $table->decimal('price', 8, 2)->nullable();

            // Merchandising
            $table->boolean('is_featured')->default(false)
                ->comment('Featured on home and listing pages');

            // Approval workflow
            // Possible values: draft | pending_review | published | rejected
            $table->string('status', 20)->default('draft');
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable()
                ->comment('Filled in by an admin when rejecting');

            // Metrics (denormalized for listing performance)
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();

            $table->timestamps();

            // Indexes for the most common frontend/admin queries.
            // Uses category_id (not subcategory_id) because Family::scopeByCategory()
            // filters directly on category_id.
            $table->index(['status', 'category_id', 'is_free'], 'families_main_filter');
            $table->index(['status', 'is_featured']);
            $table->index(['user_id', 'status']);
            $table->index('brand_id');
            $table->index('download_count');
            $table->index('view_count');
            $table->index('last_viewed_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
