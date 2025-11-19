<?php

namespace Azuriom\Plugin\InspiratoStats\Support\Court;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\CourtAction;
use Azuriom\Plugin\InspiratoStats\Models\CourtCase;
use Azuriom\Plugin\InspiratoStats\Models\CourtRevertJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourtScheduler
{
    public function __construct(
        protected CourtService $service,
        protected WebhookDispatcher $dispatcher
    ) {
    }

    public function tick(): void
    {
        $this->processReverts();
        $this->dispatcher->dispatchPending();
    }

    protected function processReverts(): void
    {
        CourtRevertJob::where('status', CourtRevertJob::STATUS_PENDING)
            ->where('run_at', '<=', now())
            ->orderBy('id')
            ->limit(25)
            ->get()
            ->each(function (CourtRevertJob $job) {
                $job->status = CourtRevertJob::STATUS_RUNNING;
                $job->save();

                try {
                    DB::transaction(function () use ($job) {
                        $action = CourtAction::with(['courtCase'])->find($job->action_id);
                        if (! $action || ! $action->courtCase) {
                            $job->status = CourtRevertJob::STATUS_FAILED;
                            $job->last_error = 'action missing';
                            $job->save();

                            return;
                        }

                        $subject = User::find($action->courtCase->user_id);

                        if (! $subject) {
                            $job->status = CourtRevertJob::STATUS_FAILED;
                            $job->last_error = 'subject missing';
                            $job->save();

                            return;
                        }

                        $this->service->revertRoleAction($action, $subject);

                        $job->status = CourtRevertJob::STATUS_COMPLETED;
                        $job->save();

                        if ($action->courtCase->actions()->where('status', '!=', 'reverted')->count() === 0) {
                            $action->courtCase->status = CourtCase::STATUS_COMPLETED;
                            $action->courtCase->finalized_at = now();
                            $action->courtCase->save();
                        }
                    });
                } catch (\Throwable $exception) {
                    Log::error('[court] revert failed', [
                        'job' => $job->id,
                        'error' => $exception->getMessage(),
                    ]);

                    $job->status = CourtRevertJob::STATUS_FAILED;
                    $job->last_error = $exception->getMessage();
                    $job->attempts++;
                    $job->run_at = now()->addMinutes(5);
                    $job->save();
                }
            });
    }
}
