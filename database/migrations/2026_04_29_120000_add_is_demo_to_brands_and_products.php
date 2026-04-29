<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (!Schema::hasColumn('brands', 'is_demo')) {
                $table->boolean('is_demo')->default(false)->after('is_active')->index();
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_demo')) {
                $table->boolean('is_demo')->default(false)->after('hidden')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'is_demo')) {
                $table->dropIndex(['is_demo']);
                $table->dropColumn('is_demo');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_demo')) {
                $table->dropIndex(['is_demo']);
                $table->dropColumn('is_demo');
            }
        });
    }
};
