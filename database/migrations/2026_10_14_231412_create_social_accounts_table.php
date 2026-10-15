<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // e.g. "google" and the provider's stable user id. No tokens are
            // stored: the provider is only used to sign the user in.
            $table->string('provider', 30);
            $table->string('provider_id', 100);

            $table->timestamps();

            // The same provider account can only be linked once.
            $table->unique(['provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};