<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quote requests and "Fale conosco" messages share one table, so the
        // admin, notifications and reports live in a single place.
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->comment('App\Enums\LeadType: quote | contact');
            $table->string('status', 20)->default('new')->comment('App\Enums\LeadStatus');
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20)->nullable()->comment('WhatsApp, digits only');
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->json('details')->nullable()->comment('Extra answers that depend on the service area');
            $table->text('notes')->nullable()->comment('Internal notes, never shown to the client');

            // Where the lead came from (marketing attribution).
            $table->string('source_url', 500)->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();

            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('privacy_accepted_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
