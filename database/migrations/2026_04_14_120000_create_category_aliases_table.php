<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->onDelete('cascade');
            $table->string('keyword', 100)->unique(); // lowercase keyword/alias
            $table->timestamps();

            $table->index('keyword');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_aliases');
    }
};
