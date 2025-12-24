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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Coupon code
            $table->text('description')->nullable();
            $table->integer('discount')->unsigned(); // Discount percentage (1-100)
            $table->date('expiry_date')->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete(); // Null for site-wide deals
            $table->integer('usage_limit')->nullable(); // Max number of times this deal can be used
            $table->integer('used_count')->default(0); // Number of times used
            $table->decimal('minimum_purchase', 10, 2)->nullable(); // Minimum purchase amount required
            $table->timestamps();
            
            $table->index('code');
            $table->index('vendor_id');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
