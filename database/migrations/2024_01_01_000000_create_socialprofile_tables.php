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
        Schema::create('socialprofile_social_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->timestamps();
        });

        Schema::create('socialprofile_activity_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('points')->default(0);
            $table->timestamps();
        });

        Schema::create('socialprofile_coin_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 18, 2)->default(0);
            $table->decimal('hold', 18, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('socialprofile_game_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('played_minutes')->default(0);
            $table->unsignedInteger('kills')->default(0);
            $table->unsignedInteger('deaths')->default(0);
            $table->json('extra_metrics')->nullable();
            $table->timestamps();
        });

        Schema::create('socialprofile_trust_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('level', ['newbie', 'verified', 'trusted', 'partner', 'staff'])->default('newbie');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('socialprofile_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['warning', 'mute', 'ban', 'other']);
            $table->text('reason');
            $table->integer('points')->default(0);
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evidence_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('socialprofile_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
            $table->string('method')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('socialprofile_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->json('scopes');
            $table->json('allowed_ips')->nullable();
            $table->json('rate_limit')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('socialprofile_api_tokens');
        Schema::dropIfExists('socialprofile_verifications');
        Schema::dropIfExists('socialprofile_violations');
        Schema::dropIfExists('socialprofile_trust_levels');
        Schema::dropIfExists('socialprofile_game_statistics');
        Schema::dropIfExists('socialprofile_coin_balances');
        Schema::dropIfExists('socialprofile_activity_points');
        Schema::dropIfExists('socialprofile_social_scores');
    }
};
