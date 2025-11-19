<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin\Automation;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Http\Requests\AutomationIntegrationRequest;
use Azuriom\Plugin\InspiratoStats\Models\AutomationIntegration;
use Azuriom\Plugin\InspiratoStats\Support\Automation\AutomationActionExecutor;
use Illuminate\Http\RedirectResponse;

class AutomationIntegrationsController extends Controller
{
    public function __construct(private readonly AutomationActionExecutor $executor)
    {
    }

    public function store(AutomationIntegrationRequest $request): RedirectResponse
    {
        $integration = AutomationIntegration::create($request->validated());
        $this->enforceDefault($integration);

        return redirect()
            ->route('socialprofile.admin.automation.index', ['tab' => 'integrations'])
            ->with('status', __('socialprofile::messages.admin.automation.integrations.created'));
    }

    public function update(AutomationIntegrationRequest $request, AutomationIntegration $integration): RedirectResponse
    {
        $integration->update($request->validated());
        $this->enforceDefault($integration);

        return redirect()
            ->route('socialprofile.admin.automation.index', ['tab' => 'integrations'])
            ->with('status', __('socialprofile::messages.admin.automation.integrations.updated'));
    }

    public function destroy(AutomationIntegration $integration): RedirectResponse
    {
        $integration->delete();

        return redirect()
            ->route('socialprofile.admin.automation.index', ['tab' => 'integrations'])
            ->with('status', __('socialprofile::messages.admin.automation.integrations.deleted'));
    }

    public function test(AutomationIntegration $integration): RedirectResponse
    {
        try {
            $result = $this->executor->testIntegration($integration);
            $message = $result['message'] ?? __('socialprofile::messages.admin.automation.integrations.test_success');

            return redirect()
                ->route('socialprofile.admin.automation.index', ['tab' => 'integrations'])
                ->with('status', $message);
        } catch (\Throwable $e) {
            return redirect()
                ->route('socialprofile.admin.automation.index', ['tab' => 'integrations'])
                ->withErrors(['integration' => $e->getMessage()]);
        }
    }

    protected function enforceDefault(AutomationIntegration $integration): void
    {
        if (! $integration->is_default) {
            return;
        }

        AutomationIntegration::query()
            ->where('type', $integration->type)
            ->where('id', '!=', $integration->id)
            ->update(['is_default' => false]);
    }
}
