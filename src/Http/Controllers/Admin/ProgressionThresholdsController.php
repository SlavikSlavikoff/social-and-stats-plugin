<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\Permission;
use Azuriom\Models\Role;
use Azuriom\Plugin\InspiratoStats\Models\AutomationIntegration;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThreshold;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProgressionThresholdsController extends Controller
{
    public function index(ProgressionRating $rating)
    {
        $thresholds = $rating->thresholds()->with('actions')->orderBy('value')->get();

        return view('socialprofile::admin.progression.thresholds', [
            'rating' => $rating,
            'thresholds' => $thresholds,
            'roles' => Role::orderBy('name')->get(),
            'permissions' => Permission::permissionsWithName(),
            'integrations' => AutomationIntegration::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, ProgressionRating $rating): RedirectResponse
    {
        $data = $this->validateThreshold($request);

        $rating->thresholds()->create($data);

        return back()->with('status', __('socialprofile::messages.progression.thresholds.created'));
    }

    public function update(Request $request, ProgressionRating $rating, ProgressionThreshold $threshold): RedirectResponse
    {
        $this->ensureThresholdRelationship($rating, $threshold);
        $data = $this->validateThreshold($request);
        $threshold->update($data);

        return back()->with('status', __('socialprofile::messages.progression.thresholds.updated'));
    }

    public function destroy(ProgressionRating $rating, ProgressionThreshold $threshold): RedirectResponse
    {
        $this->ensureThresholdRelationship($rating, $threshold);
        $threshold->delete();

        return back()->with('status', __('socialprofile::messages.progression.thresholds.deleted'));
    }

    protected function ensureThresholdRelationship(ProgressionRating $rating, ProgressionThreshold $threshold): void
    {
        if ($threshold->rating_id !== $rating->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateThreshold(Request $request): array
    {
        $data = $request->validate([
            'value' => ['required', 'integer'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:50'],
            'direction' => ['required', 'in:ascend,descend,any'],
            'is_major' => ['sometimes', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_punishment' => ['sometimes', 'boolean'],
            'band_min' => ['nullable', 'integer'],
            'band_max' => ['nullable', 'integer'],
            'metadata_json' => ['nullable', 'string'],
        ]);

        $metadata = [];

        if (! empty($data['metadata_json'])) {
            $decoded = json_decode($data['metadata_json'], true);

            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'metadata_json' => __('socialprofile::messages.progression.thresholds.invalid_metadata'),
                ]);
            }

            $metadata = $decoded;
        }

        if ($data['band_min'] !== null && $data['band_max'] !== null && (int) $data['band_min'] >= (int) $data['band_max']) {
            throw ValidationException::withMessages([
                'band_min' => __('socialprofile::messages.progression.thresholds.invalid_band'),
            ]);
        }

        return [
            'value' => (int) $data['value'],
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'icon' => $data['icon'] ?? null,
            'direction' => $data['direction'],
            'is_major' => $request->boolean('is_major'),
            'position' => $data['position'] ?? 0,
            'is_punishment' => $request->boolean('is_punishment'),
            'band_min' => array_key_exists('band_min', $data) && $data['band_min'] !== null ? (int) $data['band_min'] : null,
            'band_max' => array_key_exists('band_max', $data) && $data['band_max'] !== null ? (int) $data['band_max'] : null,
            'metadata' => $metadata ?: null,
        ];
    }
}
