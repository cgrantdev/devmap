<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive columns for optional PeptideMap Connect token expiry and
 * single-use. Both stay null on existing rows. Hashes, when enabled,
 * reuse the existing connection_token column (sha256 hex is 64 chars).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (!Schema::hasColumn('brands', 'connection_token_issued_at')) {
                $table->timestamp('connection_token_issued_at')->nullable()->after('connection_token');
            }
            if (!Schema::hasColumn('brands', 'connection_token_used_at')) {
                $table->timestamp('connection_token_used_at')->nullable()->after('connection_token_issued_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'connection_token_used_at')) {
                $table->dropColumn('connection_token_used_at');
            }
            if (Schema::hasColumn('brands', 'connection_token_issued_at')) {
                $table->dropColumn('connection_token_issued_at');
            }
        });
    }
};
