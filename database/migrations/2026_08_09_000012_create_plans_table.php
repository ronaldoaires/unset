<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('badge_label')->nullable(); // e.g. "Most popular", "Best value"
            $table->text('description')->nullable();

            // Pricing
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0); // total yearly amount

            // Mercado Pago integration
            $table->string('mp_plan_id_monthly')->nullable(); // monthly preapproval plan ID in MP
            $table->string('mp_plan_id_yearly')->nullable();  // yearly preapproval plan ID in MP

            // Trial
            $table->unsignedSmallInteger('trial_days')->default(0);

            // Usage limits
            $table->integer('max_downloads_per_month')->nullable(); // null = unlimited

            // Boolean features
            $table->boolean('has_plugin_access')->default(false);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_priority_support')->default(false);
            $table->boolean('has_exclusive_content')->default(false);

            // Granular features (e.g. which tabs/tools are unlocked)
            // e.g. {"tools": ["arch_basic"], "can_favorite": true, "can_save_history": false}
            $table->json('features')->nullable();

            // Display control
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_highlighted')->default(false); // highlighted border on landing page

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
