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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('image_url'); // Banner image URL
            $table->string('link')->nullable(); // Link URL when banner is clicked
            $table->integer('position')->default(1); // Display order
            $table->boolean('active')->default(true);
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete(); // Optional vendor association
            $table->text('description')->nullable();
            $table->date('start_date')->nullable(); // When banner should start showing
            $table->date('end_date')->nullable(); // When banner should stop showing
            $table->timestamps();
            
            $table->index('position');
            $table->index('active');
            $table->index('vendor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
