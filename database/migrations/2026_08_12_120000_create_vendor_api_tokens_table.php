<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            // SHA-256 of the plaintext token. Plaintext never stored — shown
            // once at mint time, then thrown away.
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_api_tokens');
    }
};
