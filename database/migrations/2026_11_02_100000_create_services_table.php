<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('area', 20)->comment('App\Enums\ServiceArea: engineering | technology');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('headline')->nullable()->comment('One-line promise shown at the top of the page');
            $table->string('summary', 300)->comment('Card text and default meta description');
            $table->longText('body')->nullable()->comment('Rich text with embedded images');
            $table->string('cover_path')->nullable();
            $table->string('icon', 60)->nullable()->comment('Heroicons outline name');
            $table->json('deliverables')->nullable()->comment('List of what is included');
            $table->json('faq')->nullable()->comment('List of {question, answer}');
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->index(['area', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
