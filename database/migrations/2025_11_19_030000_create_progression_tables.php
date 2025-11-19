<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('socialprofile_ratings')) {
            Schema::create('socialprofile_ratings', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('type', 50)->default('custom');
                $table->boolean('is_enabled')->default(true);
                $table->integer('scale_min')->default(0);
                $table->integer('scale_max')->default(100);
                $table->json('settings')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('socialprofile_user_ratings')) {
            Schema::create('socialprofile_user_ratings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rating_id');
                $table->unsignedInteger('user_id');
                $table->bigInteger('value')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->unique(['rating_id', 'user_id']);

                $table->foreign('rating_id')->references('id')->on('socialprofile_ratings')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('socialprofile_rating_thresholds')) {
            Schema::create('socialprofile_rating_thresholds', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rating_id');
                $table->integer('value');
                $table->string('label');
                $table->text('description')->nullable();
                $table->string('color', 32)->nullable();
                $table->string('icon', 64)->nullable();
                $table->enum('direction', ['ascend', 'descend', 'any'])->default('any');
                $table->boolean('is_major')->default(false);
                $table->unsignedInteger('position')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('rating_id')->references('id')->on('socialprofile_ratings')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('socialprofile_rating_threshold_actions')) {
            Schema::create('socialprofile_rating_threshold_actions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('threshold_id');
                $table->string('action', 50);
                $table->json('config')->nullable();
                $table->boolean('auto_revert')->default(true);
                $table->timestamps();

                $table->foreign('threshold_id')->references('id')->on('socialprofile_rating_thresholds')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('socialprofile_rating_rules')) {
            Schema::create('socialprofile_rating_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rating_id');
                $table->string('name');
                $table->string('trigger_key');
                $table->string('source_type', 50)->default('internal_event');
                $table->json('conditions')->nullable();
                $table->json('options')->nullable();
                $table->integer('delta')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('cooldown_seconds')->nullable();
                $table->timestamp('last_triggered_at')->nullable();
                $table->timestamps();
                $table->index(['trigger_key', 'is_active']);

                $table->foreign('rating_id')->references('id')->on('socialprofile_ratings')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('socialprofile_rating_events')) {
            Schema::create('socialprofile_rating_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rating_id');
                $table->unsignedInteger('user_id');
                $table->unsignedBigInteger('rule_id')->nullable();
                $table->string('source')->nullable();
                $table->bigInteger('amount');
                $table->bigInteger('value_before')->nullable();
                $table->bigInteger('value_after')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('triggered_at')->useCurrent();
                $table->timestamps();

                $table->foreign('rating_id')->references('id')->on('socialprofile_ratings')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('rule_id')->references('id')->on('socialprofile_rating_rules')->nullOnDelete();
                $table->index(['rating_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('socialprofile_rating_user_thresholds')) {
            Schema::create('socialprofile_rating_user_thresholds', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('threshold_id');
                $table->unsignedInteger('user_id');
                $table->enum('direction', ['ascend', 'descend']);
                $table->enum('action_state', ['applied', 'reverted'])->default('applied');
                $table->timestamp('reached_at')->useCurrent();
                $table->timestamp('reverted_at')->nullable();
                $table->json('context')->nullable();
                $table->timestamps();
                $table->unique(['threshold_id', 'user_id', 'direction']);

                $table->foreign('threshold_id')->references('id')->on('socialprofile_rating_thresholds')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('socialprofile_rating_user_thresholds');
        Schema::dropIfExists('socialprofile_rating_events');
        Schema::dropIfExists('socialprofile_rating_rules');
        Schema::dropIfExists('socialprofile_rating_threshold_actions');
        Schema::dropIfExists('socialprofile_rating_thresholds');
        Schema::dropIfExists('socialprofile_user_ratings');
        Schema::dropIfExists('socialprofile_ratings');
    }
};
