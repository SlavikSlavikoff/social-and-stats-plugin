<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\Automation;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\Role;
use Azuriom\Plugin\InspiratoStats\Models\AutomationIntegration;
use Azuriom\Plugin\InspiratoStats\Models\AutomationLog;
use Azuriom\Plugin\InspiratoStats\Models\AutomationRule;
use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
use Azuriom\Plugin\InspiratoStats\Support\Automation\AutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class AutomationController extends Controller
{
    public function __construct(private readonly AutomationService $automationService)
    {
    }

    public function index(Request $request)
    {
        $tab = $request->string('tab', 'rules')->toString();
        $logsQuery = AutomationLog::query()->with('rule')->latest();

        if ($request->filled('trigger')) {
            $logsQuery->where('trigger_type', $request->input('trigger'));
        }

        if ($request->filled('status')) {
            $logsQuery->where('status', $request->input('status'));
        }

        $logs = $logsQuery->limit(50)->get();
        $documentation = $this->loadDocumentation();
        $violationTypes = Lang::get('socialprofile::messages.violations.types');
        $courtStatuses = Lang::get('socialprofile::messages.court.statuses');
        $courtActions = Lang::get('socialprofile::messages.court.actions');
        $courtModes = Lang::get('socialprofile::messages.court.modes');

        return view('socialprofile::admin.automation.index', [
            'tab' => $tab,
            'rules' => AutomationRule::query()->orderByDesc('priority')->orderBy('id')->get(),
            'integrations' => AutomationIntegration::query()->orderBy('name')->get(),
            'logs' => $logs,
            'logFilters' => [
                'trigger' => $request->input('trigger'),
                'status' => $request->input('status'),
            ],
            'roles' => Role::query()->orderBy('name')->get(),
            'trustLevels' => TrustLevel::LEVELS,
            'triggers' => config('socialprofile.automation.triggers'),
            'actionTypes' => config('socialprofile.automation.actions'),
            'placeholders' => config('socialprofile.automation.placeholders'),
            'scheduler' => $this->automationService->schedulerSettings(),
            'lastSchedulerRun' => setting('socialprofile_automation_monthly_last_run'),
            'documentation' => $documentation,
            'violationTypes' => is_array($violationTypes) ? $violationTypes : [],
            'courtStatuses' => is_array($courtStatuses) ? $courtStatuses : [],
            'courtActions' => is_array($courtActions) ? $courtActions : [],
            'courtModes' => is_array($courtModes) ? $courtModes : [],
        ]);
    }

    protected function loadDocumentation(): ?string
    {
        $path = config('socialprofile.automation.documentation.path');

        if ($path && file_exists($path)) {
            return Str::markdown(file_get_contents($path));
        }

        return null;
    }
}
