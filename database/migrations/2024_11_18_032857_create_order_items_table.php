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
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->morphs('order_itemable');
                $table->string('name')->nullable();
                $table->string('sku')->nullable();
                $table->string('currency', 10)->default('USD');
                $table->integer('quantity')->default(1);
                $table->decimal('unit_amount', 10, 2)->default(0);
                $table->decimal('amount', 10, 2);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->unsignedInteger('billing_interval_count')->nullable();
                $table->string('billing_interval')->nullable(); // day, week, month, year
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
        Schema::dropIfExists('order_items');
    }
};
