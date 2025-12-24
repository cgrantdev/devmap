<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendor_reviews', function (Blueprint $table) {
            // Add status enum field (pending, approved, rejected)
            // We'll keep is_approved for backward compatibility but add status for better management
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('is_approved');
            $table->boolean('verified')->default(false)->after('status'); // Verified purchase flag
            $table->index('status');
        });
        
        // Migrate existing data: set status based on is_approved
        DB::statement("UPDATE vendor_reviews SET status = CASE WHEN is_approved = 1 THEN 'approved' ELSE 'pending' END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_reviews', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'verified']);
        });
    }
};
