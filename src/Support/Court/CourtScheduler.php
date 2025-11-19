<?php

namespace Azuriom\Plugin\InspiratoStats\Support\Court;

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
            ->with(['action.courtCase.actions', 'action.courtCase.subject'])
            ->orderBy('id')
            ->limit(25)
            ->get()
            ->each(function (CourtRevertJob $job) {
                $job->status = CourtRevertJob::STATUS_RUNNING;
                $job->save();

                try {
                    DB::transaction(function () use ($job) {
                        $action = $job->action;
                        $case = $action?->courtCase;
                        $subject = $case?->subject;

                        if (! $action || ! $case) {
                            $job->status = CourtRevertJob::STATUS_FAILED;
                            $job->last_error = 'action missing';
                            $job->save();

                            return;
                        }

                        if (! $subject) {
                            $job->status = CourtRevertJob::STATUS_FAILED;
                            $job->last_error = 'subject missing';
                            $job->save();

                            return;
                        }

                        $this->service->revertRoleAction($action, $subject);
                        $case->actions->each(function (CourtAction $caseAction) use ($action): void {
                            if ($caseAction->id === $action->id) {
                                $caseAction->status = 'reverted';
                            }
                        });

                        $job->status = CourtRevertJob::STATUS_COMPLETED;
                        $job->save();

                        if ($case->actions->firstWhere('status', '!=', 'reverted') === null) {
                            $case->status = CourtCase::STATUS_COMPLETED;
                            $case->finalized_at = now();
                            $case->save();
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
