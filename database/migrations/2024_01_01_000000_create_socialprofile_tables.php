<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->dropIfForeignKeysIncompatible('socialprofile_social_scores', ['user_id']);

        if (! Schema::hasTable('socialprofile_social_scores')) {
            Schema::create('socialprofile_social_scores', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->unique();
                $table->unsignedInteger('score')->default(0);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        $this->dropIfForeignKeysIncompatible('socialprofile_activity_points', ['user_id']);

        if (! Schema::hasTable('socialprofile_activity_points')) {
            Schema::create('socialprofile_activity_points', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->unique();
                $table->unsignedBigInteger('points')->default(0);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        $this->dropIfForeignKeysIncompatible('socialprofile_coin_balances', ['user_id']);

        if (! Schema::hasTable('socialprofile_coin_balances')) {
            Schema::create('socialprofile_coin_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->unique();
                $table->decimal('balance', 18, 2)->default(0);
                $table->decimal('hold', 18, 2)->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        $this->dropIfForeignKeysIncompatible('socialprofile_game_statistics', ['user_id']);

        if (! Schema::hasTable('socialprofile_game_statistics')) {
            Schema::create('socialprofile_game_statistics', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->unique();
                $table->unsignedBigInteger('played_minutes')->default(0);
                $table->unsignedInteger('kills')->default(0);
                $table->unsignedInteger('deaths')->default(0);
                $table->json('extra_metrics')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        $this->dropIfForeignKeysIncompatible('socialprofile_trust_levels', ['user_id', 'granted_by']);

        if (! Schema::hasTable('socialprofile_trust_levels')) {
            Schema::create('socialprofile_trust_levels', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->unique();
                $table->enum('level', ['newbie', 'verified', 'trusted', 'partner', 'staff'])->default('newbie');
                $table->unsignedInteger('granted_by')->nullable();
                $table->string('note')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('granted_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        $this->dropIfForeignKeysIncompatible('socialprofile_violations', ['user_id', 'issued_by']);

        if (! Schema::hasTable('socialprofile_violations')) {
            Schema::create('socialprofile_violations', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->enum('type', ['warning', 'mute', 'ban', 'other']);
                $table->text('reason');
                $table->integer('points')->default(0);
                $table->unsignedInteger('issued_by')->nullable();
                $table->string('evidence_url')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['user_id', 'created_at']);

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        $this->dropIfForeignKeysIncompatible('socialprofile_verifications', ['user_id']);

        if (! Schema::hasTable('socialprofile_verifications')) {
            Schema::create('socialprofile_verifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->unique();
                $table->enum('status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
                $table->string('method')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        $this->dropIfForeignKeysIncompatible('socialprofile_api_tokens', ['created_by']);

        if (! Schema::hasTable('socialprofile_api_tokens')) {
            Schema::create('socialprofile_api_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('token_hash', 64)->unique();
                $table->json('scopes');
                $table->json('allowed_ips')->nullable();
                $table->json('rate_limit')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }
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

    /**
     * Drop an existing table if one of its foreign key columns is incompatible with the users.id column.
     */
    protected function dropIfForeignKeysIncompatible(string $table, array $columns): void
    {
        if (! Schema::hasTable($table) || Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $expected = $this->normalizedColumnType('users', 'id');

        foreach ($columns as $column) {
            $columnInfo = $this->normalizedColumnType($table, $column);

            if ($columnInfo === null || $columnInfo !== $expected) {
                Schema::drop($table);

                return;
            }
        }
    }

    protected function normalizedColumnType(string $table, string $column): ?string
    {
        $definition = DB::selectOne('SHOW COLUMNS FROM '.$table.' WHERE Field = ?', [$column]);

        if ($definition === null) {
            return null;
        }

        $type = strtolower($definition->Type);
        $type = preg_replace('/\\(.*\\)/', '', $type);
        $type = str_replace('unsigned', ' unsigned', $type);

        return trim($type);
    }
};
