<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTemplatesTable();
        $this->createCasesTable();
        $this->createActionsTable();
        $this->createSnapshotsTable();
        $this->createRevertJobsTable();
        $this->createLogsTable();
        $this->createAttachmentsTable();
        $this->createWebhooksTable();
        $this->createWebhookDeliveriesTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('socialprofile_court_webhook_deliveries');
        Schema::dropIfExists('socialprofile_court_webhooks');
        Schema::dropIfExists('socialprofile_court_attachments');
        Schema::dropIfExists('socialprofile_court_logs');
        Schema::dropIfExists('socialprofile_court_revert_jobs');
        Schema::dropIfExists('socialprofile_court_state_snapshots');
        Schema::dropIfExists('socialprofile_court_actions');
        Schema::dropIfExists('socialprofile_court_cases');
        Schema::dropIfExists('socialprofile_court_templates');
    }

    protected function createTemplatesTable(): void
    {
        if (! $this->shouldCreateCourtTable('socialprofile_court_templates', ['key', 'payload', 'default_executor'])) {
            return;
        }

        Schema::create('socialprofile_court_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('base_comment')->nullable();
            $table->json('payload');
            $table->json('limits')->nullable();
            $table->string('default_executor')->default('site');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    protected function createCasesTable(): void
    {
        if (! $this->shouldCreateCourtTable('socialprofile_court_cases', ['case_number', 'user_id', 'judge_id', 'status'])) {
            return;
        }

        Schema::create('socialprofile_court_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('judge_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->enum('mode', ['auto', 'manual']);
            $table->enum('executor', ['site', 'discord', 'minecraft'])->default('site');
            $table->enum('status', ['draft', 'issued', 'active', 'awaiting_revert', 'completed', 'cancelled', 'revoked'])->default('issued');
            $table->enum('visibility', ['private', 'judges', 'public'])->default('judges');
            $table->unsignedBigInteger('continued_case_id')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->text('comment')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('payload')->nullable();
            $table->json('metrics_applied')->nullable();
            $table->unsignedInteger('created_by_executor_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['judge_id', 'status']);
            $table->index(['expires_at']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('judge_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('template_id')->references('id')->on('socialprofile_court_templates')->nullOnDelete();
            $table->foreign('continued_case_id')->references('id')->on('socialprofile_court_cases')->nullOnDelete();
        });
    }

    protected function createActionsTable(): void
    {
        if (! $this->shouldCreateCourtTable('socialprofile_court_actions', ['case_id', 'type', 'status'])) {
            return;
        }

        Schema::create('socialprofile_court_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->enum('type', ['ban', 'mute', 'metric', 'role', 'note']);
            $table->string('metric_key')->nullable();
            $table->integer('delta')->nullable();
            $table->string('currency')->nullable();
            $table->unsignedInteger('role_id')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->boolean('allow_zero_cancel')->default(false);
            $table->enum('status', ['pending', 'applied', 'awaiting_revert', 'reverted', 'cancelled'])->default('pending');
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('case_id')->references('id')->on('socialprofile_court_cases')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    protected function createSnapshotsTable(): void
    {
        if (! $this->shouldCreateCourtTable('socialprofile_court_state_snapshots', ['action_id', 'snapshot'])) {
            return;
        }

        Schema::create('socialprofile_court_state_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('action_id');
            $table->unsignedInteger('user_id');
            $table->json('snapshot');
            $table->timestamps();

            $table->foreign('action_id')->references('id')->on('socialprofile_court_actions')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    protected function createRevertJobsTable(): void
    {
        if (! $this->shouldCreateCourtTable('socialprofile_court_revert_jobs', ['action_id', 'run_at', 'status'])) {
            return;
        }

        Schema::create('socialprofile_court_revert_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('action_id');
            $table->timestamp('run_at');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['run_at', 'status']);

            $table->foreign('action_id')->references('id')->on('socialprofile_court_actions')->cascadeOnDelete();
        });
    }

    protected function createLogsTable(): void
    {
        if (! $this->shouldCreateCourtTable('socialprofile_court_logs', ['case_id', 'event'])) {
            return;
        }

        Schema::create('socialprofile_court_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->string('event');
            $table->string('channel')->nullable();
            $table->unsignedInteger('actor_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['case_id', 'created_at']);

            $table->foreign('case_id')->references('id')->on('socialprofile_court_cases')->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    protected function createAttachmentsTable(): void
    {
        if (! $this->shouldCreateCourtTable('socialprofile_court_attachments', ['case_id', 'path'])) {
            return;
        }

        Schema::create('socialprofile_court_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->string('type')->default('link');
            $table->string('path');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('case_id')->references('id')->on('socialprofile_court_cases')->cascadeOnDelete();
        });
    }

    protected function createWebhooksTable(): void
    {
        if (! $this->shouldCreateCourtTable('socialprofile_court_webhooks', ['url', 'events'])) {
            return;
        }

        Schema::create('socialprofile_court_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    protected function createWebhookDeliveriesTable(): void
    {
        if (! $this->shouldCreateCourtTable('socialprofile_court_webhook_deliveries', ['webhook_id', 'event', 'status'])) {
            return;
        }

        Schema::create('socialprofile_court_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('webhook_id');
            $table->unsignedBigInteger('case_id')->nullable();
            $table->string('event');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->json('payload');
            $table->timestamps();
            $table->index(['status', 'next_attempt_at'], 'court_webhook_deliveries_status_next_idx');

            $table->foreign('webhook_id')->references('id')->on('socialprofile_court_webhooks')->cascadeOnDelete();
            $table->foreign('case_id')->references('id')->on('socialprofile_court_cases')->nullOnDelete();
        });
    }

    protected function shouldCreateCourtTable(string $table, array $requiredColumns): bool
    {
        if (! Schema::hasTable($table)) {
            return true;
        }

        foreach ($requiredColumns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                Schema::drop($table);

                return true;
            }
        }

        return false;
    }
};
