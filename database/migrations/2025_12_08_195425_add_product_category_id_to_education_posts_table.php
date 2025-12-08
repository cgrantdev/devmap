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
        Schema::table('education_posts', function (Blueprint $table) {
            $table->foreignId('product_category_id')->nullable()->unique()->after('id')->constrained('product_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('education_posts', function (Blueprint $table) {
            $table->dropForeign(['product_category_id']);
            $table->dropUnique(['product_category_id']);
            $table->dropColumn('product_category_id');
        });
    }
};
