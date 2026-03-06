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
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('subscription_id')->nullable()->index();
                $table->foreignId('payment_gateway_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type')->default('charge'); // charge, renewal, refund, adjustment
                $table->string('direction')->default('debit'); // debit, credit
                $table->string('status')->default('pending'); // pending, succeeded, failed, refunded, cancelled
                $table->decimal('amount', 10, 2);
                $table->string('currency', 10)->default('USD');
                $table->string('payment_method')->nullable();
                $table->string('external_id')->nullable()->unique();
                $table->string('ref_id')->nullable();
                $table->boolean('is_fraud')->default(false);
                $table->timestamp('processed_at')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
