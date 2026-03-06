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
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('status')->default('active');
                $table->string('type')->default('virtual'); // virtual, physical
                $table->string('sale_type')->default('one_time'); // one_time, recurring
                $table->decimal('price', 10, 2);
                $table->string('currency', 10)->default('USD');
                $table->integer('stock')->nullable();
                $table->boolean('is_variable')->default(false);
                $table->json('properties')->nullable(); // Fixed {"version": "1.0", "color": "red"}
                $table->json('variants')->nullable(); // Selectable {"color": ["red", "blue"], "size": ["s", "m", "l"]}
                $table->unsignedInteger('billing_interval_count')->nullable();
                $table->string('billing_interval')->nullable(); // day, week, month, year
                $table->unsignedInteger('grace_days')->nullable();
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
        Schema::dropIfExists('products');
    }
};
