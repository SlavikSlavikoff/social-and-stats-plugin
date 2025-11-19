<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Database\Seeders\CourtTemplateSeeder;
use Azuriom\Plugin\InspiratoStats\Models\CourtTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CourtTemplatesController extends Controller
{
    public function index()
    {
        $templates = CourtTemplate::where('is_active', true)->orderBy('name')->get();

        return view('socialprofile::admin.court.templates.index', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTemplate($request);

        CourtTemplate::create($data);

        return redirect()
            ->route('socialprofile.admin.court.templates.index')
            ->with('status', __('socialprofile::messages.court.templates.saved'));
    }

    public function update(Request $request, CourtTemplate $template): RedirectResponse
    {
        $data = $this->validateTemplate($request, $template->id);

        $template->update($data);

        return redirect()
            ->route('socialprofile.admin.court.templates.index')
            ->with('status', __('socialprofile::messages.court.templates.saved'));
    }

    public function destroy(CourtTemplate $template): RedirectResponse
    {
        $template->update(['is_active' => false]);

        return redirect()
            ->route('socialprofile.admin.court.templates.index')
            ->with('status', __('socialprofile::messages.court.templates.archived'));
    }

    public function refresh(Request $request): RedirectResponse
    {
        setting()->set('socialprofile_court_templates_seeded', 0);
        App::make(CourtTemplateSeeder::class)->run();

        return redirect()
            ->route('socialprofile.admin.court.templates.index')
            ->with('status', __('socialprofile::messages.court.templates.refreshed'));
    }

    protected function validateTemplate(Request $request, ?int $ignoreId = null): array
    {
        $payload = $this->decodePayload($request->input('payload'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => [
                'required',
                'string',
                'max:64',
                Rule::unique('socialprofile_court_templates', 'key')->ignore($ignoreId),
            ],
            'base_comment' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
            'payload' => ['nullable', 'string'],
        ]);

        $data['payload'] = $payload;
        $data['limits'] = null;
        $data['is_active'] = (bool) ($request->boolean('is_active') || $request->input('is_active'));
        $data['default_executor'] = config('socialprofile.court.default_executor', 'site');

        return $data;
    }

    protected function decodePayload(?string $payload): array
    {
        if ($payload === null) {
            return [];
        }

        $decoded = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw ValidationException::withMessages([
                'payload' => __('socialprofile::messages.court.templates.invalid_payload'),
            ]);
        }

        $allowedKeys = ['punishment', 'ban', 'mute', 'unverify', 'role', 'comment'];
        foreach ($decoded as $key => $_) {
            if (! in_array($key, $allowedKeys, true)) {
                throw ValidationException::withMessages([
                    'payload' => __('socialprofile::messages.court.templates.invalid_payload'),
                ]);
            }
        }

        return $decoded;
    }
}
