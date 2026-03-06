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
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('status')->default('active');
                $table->integer('free_trial_duration')->default(0)->nullable();
                $table->boolean('is_recurring')->default(true);
                $table->unsignedInteger('billing_interval_count')->default(1);
                $table->string('billing_interval')->default('month'); // day, week, month, year
                $table->unsignedInteger('grace_days')->default(3);
                $table->decimal('price_amount', 10, 2)->default(0);
                $table->decimal('setup_fee_amount', 10, 2)->default(0);
                $table->string('currency', 10)->default('USD');
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
        Schema::dropIfExists('plans');
    }
};
