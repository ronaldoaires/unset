<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Browser sign-in for desktop clients (OAuth 2.0 device authorization flow,
 * RFC 8628): the plugin shows a short code, the user approves it on the site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_authorizations', function (Blueprint $table) {
            $table->id();
            // Only the SHA-256 of the secret the client polls with is stored.
            $table->char('device_code_hash', 64)->unique();
            // Short code typed or confirmed by the user, stored without the dash.
            $table->string('user_code', 16)->unique();
            $table->string('device_name', 100);
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('denied_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_authorizations');
    }
};
