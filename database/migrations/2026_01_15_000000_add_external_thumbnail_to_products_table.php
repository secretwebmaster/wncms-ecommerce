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
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'external_thumbnail')) {
            Schema::table('products', function (Blueprint $table) {
                $table->text('external_thumbnail')->nullable()->after('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'external_thumbnail')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('external_thumbnail');
            });
        }
    }
};
