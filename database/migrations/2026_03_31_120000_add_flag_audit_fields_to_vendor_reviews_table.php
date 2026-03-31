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
        Schema::table('vendor_reviews', function (Blueprint $table) {
            $table->foreignId('flag_reviewed_by')
                ->nullable()
                ->after('flag_reason')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('flag_reviewed_at')->nullable()->after('flag_reviewed_by');
            $table->string('flag_resolution', 50)->nullable()->after('flag_reviewed_at');
            $table->text('flag_resolution_note')->nullable()->after('flag_resolution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('flag_reviewed_by');
            $table->dropColumn([
                'flag_reviewed_at',
                'flag_resolution',
                'flag_resolution_note',
            ]);
        });
    }
};
