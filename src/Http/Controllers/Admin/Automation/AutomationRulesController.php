<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\Automation;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Http\Requests\AutomationRuleRequest;
use Azuriom\Plugin\InspiratoStats\Models\AutomationRule;

class AutomationRulesController extends Controller
{
    public function store(AutomationRuleRequest $request)
    {
        AutomationRule::create($request->validated());

        return redirect()
            ->route('socialprofile.admin.automation.index', ['tab' => 'rules'])
            ->with('status', __('socialprofile::messages.admin.automation.rules.created'));
    }

    public function update(AutomationRuleRequest $request, AutomationRule $rule)
    {
        $rule->update($request->validated());

        return redirect()
            ->route('socialprofile.admin.automation.index', ['tab' => 'rules'])
            ->with('status', __('socialprofile::messages.admin.automation.rules.updated'));
    }

    public function destroy(AutomationRule $rule)
    {
        $rule->delete();

        return redirect()
            ->route('socialprofile.admin.automation.index', ['tab' => 'rules'])
            ->with('status', __('socialprofile::messages.admin.automation.rules.deleted'));
    }
}
