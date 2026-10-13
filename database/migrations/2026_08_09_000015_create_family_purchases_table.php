<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, paid, refunded
            $table->string('payment_id')->nullable();     // external Mercado Pago ID
            $table->string('payment_method')->nullable(); // pix, credit_card, etc.
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // A user can only purchase a given family once
            $table->unique(['user_id', 'family_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_purchases');
    }
};
