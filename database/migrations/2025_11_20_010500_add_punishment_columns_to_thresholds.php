<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('socialprofile_rating_thresholds', function (Blueprint $table) {
            if (! Schema::hasColumn('socialprofile_rating_thresholds', 'is_punishment')) {
                $table->boolean('is_punishment')->default(false)->after('direction');
            }

            if (! Schema::hasColumn('socialprofile_rating_thresholds', 'band_min')) {
                $table->integer('band_min')->nullable()->after('is_punishment');
            }

            if (! Schema::hasColumn('socialprofile_rating_thresholds', 'band_max')) {
                $table->integer('band_max')->nullable()->after('band_min');
            }
        });
    }

    public function down(): void
    {
        Schema::table('socialprofile_rating_thresholds', function (Blueprint $table) {
            if (Schema::hasColumn('socialprofile_rating_thresholds', 'band_max')) {
                $table->dropColumn('band_max');
            }

            if (Schema::hasColumn('socialprofile_rating_thresholds', 'band_min')) {
                $table->dropColumn('band_min');
            }

            if (Schema::hasColumn('socialprofile_rating_thresholds', 'is_punishment')) {
                $table->dropColumn('is_punishment');
            }
        });
    }
};
