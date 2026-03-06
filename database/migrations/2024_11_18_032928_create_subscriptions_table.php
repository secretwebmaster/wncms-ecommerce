<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->string('status')->default('pending'); // pending, trialing, active, past_due, cancelled, expired
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('price_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('payment_gateway_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('last_transaction_id')->nullable()->index();
                $table->string('currency', 10)->default('USD');
                $table->decimal('amount', 10, 2)->default(0);
                $table->unsignedInteger('billing_interval_count')->default(1);
                $table->string('billing_interval')->default('month');
                $table->unsignedInteger('grace_days')->default(3);
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('subscribed_at');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('current_period_start')->nullable();
                $table->timestamp('current_period_end')->nullable();
                $table->timestamp('next_billing_at')->nullable();
                $table->boolean('cancel_at_period_end')->default(false);
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('expired_at')->nullable();
                $table->json('attributes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
