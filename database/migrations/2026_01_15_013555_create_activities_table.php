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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // e.g., 'vendor_registered', 'product_added', 'review_submitted', 'deal_created', 'banner_activated'
            $table->string('description'); // e.g., 'New vendor registered', 'Product added'
            $table->string('entity_name'); // e.g., 'Peptide Pro Labs', 'Swiss Chems'
            $table->string('entity_type')->nullable(); // e.g., 'vendor', 'product', 'review', 'deal', 'banner'
            $table->unsignedBigInteger('entity_id')->nullable(); // ID of the related entity
            $table->timestamps();
            
            $table->index('type');
            $table->index('created_at');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
