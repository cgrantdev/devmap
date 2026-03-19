<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_reviews', function (Blueprint $table) {
            $table->text('vendor_reply')->nullable()->after('review');
            $table->timestamp('vendor_replied_at')->nullable()->after('vendor_reply');
            $table->boolean('flagged')->default(false)->after('vendor_replied_at');
            $table->text('flag_reason')->nullable()->after('flagged');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_reviews', function (Blueprint $table) {
            $table->dropColumn(['vendor_reply', 'vendor_replied_at', 'flagged', 'flag_reason']);
        });
    }
};
