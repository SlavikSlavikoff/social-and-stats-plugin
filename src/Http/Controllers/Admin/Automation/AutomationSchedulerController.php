<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\Automation;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Http\Requests\AutomationSchedulerRequest;

class AutomationSchedulerController extends Controller
{
    public function update(AutomationSchedulerRequest $request)
    {
        $data = $request->validated();

        setting()->set('socialprofile_automation_monthly_enabled', $data['enabled']);
        setting()->set('socialprofile_automation_monthly_day', $data['day']);
        setting()->set('socialprofile_automation_monthly_hour', $data['hour']);
        setting()->set('socialprofile_automation_monthly_limit', $data['top_limit']);
        setting()->set('socialprofile_automation_monthly_sources', $data['sources']);
        setting()->set('socialprofile_automation_monthly_reward', $data['reward']);

        return redirect()
            ->route('socialprofile.admin.automation.index', ['tab' => 'scheduler'])
            ->with('status', __('socialprofile::messages.admin.automation.scheduler.saved'));
    }
}
