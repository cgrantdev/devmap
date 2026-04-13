<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('education_posts', 'show_in_encyclopedia')) {
                $table->boolean('show_in_encyclopedia')->default(true)->after('status')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('education_posts', function (Blueprint $table) {
            if (Schema::hasColumn('education_posts', 'show_in_encyclopedia')) {
                $table->dropIndex(['show_in_encyclopedia']);
                $table->dropColumn('show_in_encyclopedia');
            }
        });
    }
};
