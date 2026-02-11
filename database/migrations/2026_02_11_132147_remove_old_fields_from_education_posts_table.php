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
            $table->dropColumn(['overview', 'content', 'shop_url', 'accordion_sections']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('education_posts', function (Blueprint $table) {
            $table->longText('overview')->nullable();
            $table->longText('content')->nullable();
            $table->string('shop_url')->nullable();
            $table->json('accordion_sections')->nullable();
        });
    }
};
