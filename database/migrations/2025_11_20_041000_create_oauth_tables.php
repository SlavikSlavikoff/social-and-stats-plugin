<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socialprofile_oauth_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_user_id');
            $table->string('access_token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->string('id_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
        });

        Schema::create('socialprofile_oauth_login_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider');
            $table->string('status')->default('pending');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('error_code')->nullable();
            $table->json('result_payload')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socialprofile_oauth_login_sessions');
        Schema::dropIfExists('socialprofile_oauth_identities');
    }
};
