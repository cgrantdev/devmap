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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'purity')) {
                $table->decimal('purity', 5, 2)->nullable()->after('size_mg');
            }
            if (!Schema::hasColumn('products', 'featured')) {
                $table->boolean('featured')->default(false)->after('hidden')->index();
            }
            if (!Schema::hasColumn('products', 'lab_tested')) {
                $table->boolean('lab_tested')->default(false)->after('featured')->index();
            }
            if (!Schema::hasColumn('products', 'first_timer_deals')) {
                $table->boolean('first_timer_deals')->default(false)->after('lab_tested')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'first_timer_deals')) {
                $table->dropIndex(['first_timer_deals']);
                $table->dropColumn('first_timer_deals');
            }
            if (Schema::hasColumn('products', 'lab_tested')) {
                $table->dropIndex(['lab_tested']);
                $table->dropColumn('lab_tested');
            }
            if (Schema::hasColumn('products', 'featured')) {
                $table->dropIndex(['featured']);
                $table->dropColumn('featured');
            }
            if (Schema::hasColumn('products', 'purity')) {
                $table->dropColumn('purity');
            }
        });
    }
};
