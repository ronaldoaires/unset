<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Portfolio: real jobs that prove each service.
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            // Restrict: a service with portfolio projects cannot be deleted by accident.
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 300)->comment('Card text and default meta description');
            $table->longText('body')->nullable()->comment('Rich text with embedded images');
            $table->string('cover_path');
            $table->string('location')->nullable()->comment('City/UF, e.g. Nova Xavantina/MT');
            $table->unsignedSmallInteger('year')->nullable();
            $table->decimal('built_area', 10, 2)->nullable()->comment('Square meters (engineering projects)');
            $table->string('external_url')->nullable()->comment('Live site or app (technology projects)');
            $table->string('client_name')->nullable()->comment('Only shown with the client\'s permission');
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'is_active', 'sort_order']);
            $table->index(['is_active', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
