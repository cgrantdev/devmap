<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor-submitted claims for cGMP-manufacturing and independent-testing
 * badges. Vendors submit a document (COA, cert, facility audit) — Julia
 * reviews it in an admin queue — approved claims light up the badge on
 * that vendor's card + storefront and are announced in Discord.
 *
 * See docs/vendor-certifications.md for full lifecycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_certification_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            // Nullable so an admin can pre-create a claim if needed
            // (e.g. Julia uploads a doc a vendor emailed her).
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            // 'cgmp'       — cGMP-compliant manufacturing (facility audit / cert)
            // 'testing_7x' — Independent testing on 7+ compounds (batch COAs)
            // ENUM would be tighter but the app enforces the whitelist and
            // string leaves room to add new tags without a schema change.
            $table->string('type', 40);
            // Doc lives in storage/app/certifications/{claim_id}/{filename}
            // Never public. Admin fetches via a signed download route.
            $table->string('document_path', 1024)->nullable();
            $table->string('document_original_name', 255)->nullable();
            $table->text('notes')->nullable();

            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->text('admin_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // A vendor can only hold ONE active claim per type. Re-submitting
            // reuses the row (update path); rejections clear so they can
            // resubmit with new documentation.
            $table->unique(['brand_id', 'type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_certification_claims');
    }
};
