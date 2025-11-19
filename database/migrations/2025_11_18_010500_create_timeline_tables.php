<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socialprofile_timelines', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('intro_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_period_labels')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });

        Schema::create('socialprofile_timeline_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeline_id')->constrained('socialprofile_timelines')->cascadeOnDelete();
            $table->string('title');
            $table->string('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['timeline_id', 'position']);
        });

        Schema::create('socialprofile_timeline_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeline_id')->constrained('socialprofile_timelines')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('socialprofile_timeline_periods')->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image_path')->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->json('items');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('highlight')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['timeline_id', 'position']);
            $table->index(['period_id', 'position']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socialprofile_timeline_cards');
        Schema::dropIfExists('socialprofile_timeline_periods');
        Schema::dropIfExists('socialprofile_timelines');
    }
};
