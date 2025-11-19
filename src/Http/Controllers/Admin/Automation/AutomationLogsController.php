<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\Automation;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Models\AutomationLog;
use Azuriom\Plugin\InspiratoStats\Support\Automation\AutomationService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class AutomationLogsController extends Controller
{
    public function __construct(private readonly AutomationService $automationService)
    {
    }

    public function replay(AutomationLog $log): RedirectResponse
    {
        try {
            $this->automationService->replayLog($log);

            return redirect()
                ->route('socialprofile.admin.automation.index', ['tab' => 'logs'])
                ->with('status', __('socialprofile::messages.admin.automation.logs.replayed'));
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('socialprofile.admin.automation.index', ['tab' => 'logs'])
                ->withErrors(['log' => $exception->getMessage()]);
        }
    }
}
