<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            // Serves the account history (per user and family, latest date) and
            // the monthly quota check in User::canDownload().
            $table->index(['user_id', 'family_id', 'downloaded_at'], 'downloads_user_family_date');
        });
    }

    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropIndex('downloads_user_family_date');
        });
    }
};