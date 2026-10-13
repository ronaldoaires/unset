<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('previous_plan_id')->nullable()->constrained('plans')->nullOnDelete();

            // Billing cycle for this subscription
            $table->enum('billing_cycle', ['monthly', 'yearly']);

            // Status: active, canceled, trial, past_due, expired
            $table->string('status');

            // Mercado Pago integration
            $table->string('mp_preapproval_id')->nullable()->unique(); // subscription ID in MP
            $table->string('mp_payer_id')->nullable();                 // payer ID in MP
            $table->string('mp_external_reference')->nullable();       // reference sent to MP

            // Date tracking
            $table->timestamp('started_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('last_payment_at')->nullable();

            $table->decimal('last_payment_amount', 10, 2)->nullable();

            // Cancellation reason (for churn analysis)
            $table->string('cancel_reason')->nullable();

            // Raw payload of the last received webhook (debugging/auditing)
            $table->json('mp_metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
