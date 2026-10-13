<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();

            // Mercado Pago identifiers
            $table->string('mp_payment_id')->unique();       // individual payment ID
            $table->string('mp_preapproval_id')->nullable(); // reference to the parent subscription

            // Payment data
            $table->enum('status', ['approved', 'rejected', 'pending', 'refunded', 'cancelled']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BRL');
            $table->string('payment_method')->nullable(); // credit_card, pix, debit_card, etc.
            $table->string('payment_type')->nullable();   // recurring, first_payment

            $table->timestamp('paid_at')->nullable();

            // Raw webhook payload (essential for auditing and debugging)
            $table->json('mp_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
