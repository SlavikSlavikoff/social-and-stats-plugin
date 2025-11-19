<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProgressionRatingsController extends Controller
{
    public function index(Request $request)
    {
        $ratings = ProgressionRating::orderBy('sort_order')->get();
        $editing = null;

        if ($request->filled('edit')) {
            $editing = $ratings->firstWhere('id', (int) $request->integer('edit'));
        }

        return view('socialprofile::admin.progression.index', [
            'ratings' => $ratings,
            'editingRating' => $editing,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRating($request);
        ProgressionRating::create($data);

        return redirect()->route('socialprofile.admin.progression.index')
            ->with('status', __('socialprofile::messages.progression.ratings.created'));
    }

    public function update(Request $request, ProgressionRating $rating): RedirectResponse
    {
        $data = $this->validateRating($request, $rating->id);
        $rating->update($data);

        return redirect()->route('socialprofile.admin.progression.index', ['edit' => $rating->id])
            ->with('status', __('socialprofile::messages.progression.ratings.updated'));
    }

    public function destroy(ProgressionRating $rating): RedirectResponse
    {
        $rating->delete();

        return back()->with('status', __('socialprofile::messages.progression.ratings.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateRating(Request $request, ?int $ratingId = null): array
    {
        $types = [
            ProgressionRating::TYPE_SOCIAL,
            ProgressionRating::TYPE_ACTIVITY,
            ProgressionRating::TYPE_CUSTOM,
        ];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('socialprofile_ratings', 'slug')->ignore($ratingId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in($types)],
            'scale_min' => ['required', 'integer'],
            'scale_max' => ['required', 'integer'],
            'visual_min' => ['nullable', 'integer'],
            'visual_max' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:20'],
            'unit' => ['nullable', 'string', 'max:20'],
            'display_zero' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['sometimes', 'boolean'],
            'support_threshold' => ['nullable', 'integer', 'min:0'],
            'support_meta_key' => ['nullable', 'string', 'max:50'],
        ]);

        if ((int) $data['scale_min'] >= (int) $data['scale_max']) {
            throw ValidationException::withMessages([
                'scale_min' => __('socialprofile::messages.progression.ratings.invalid_scale'),
            ]);
        }

        if ($data['visual_min'] !== null && $data['visual_max'] !== null && (int) $data['visual_min'] >= (int) $data['visual_max']) {
            throw ValidationException::withMessages([
                'visual_min' => __('socialprofile::messages.progression.ratings.invalid_visual_scale'),
            ]);
        }

        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'scale_min' => (int) $data['scale_min'],
            'scale_max' => (int) $data['scale_max'],
            'sort_order' => $data['sort_order'] ?? null,
            'is_enabled' => $request->boolean('is_enabled', true),
            'settings' => [
                'color' => $data['color'] ?? '#38b2ac',
                'unit' => $data['unit'] ?? null,
                'display_zero' => (int) ($data['display_zero'] ?? 0),
                'support_threshold' => $data['support_threshold'] ?? null,
                'support_meta_key' => ($data['support_meta_key'] ?? null) ?: 'support_points',
                'visual_min' => $data['visual_min'] !== null ? (int) $data['visual_min'] : null,
                'visual_max' => $data['visual_max'] !== null ? (int) $data['visual_max'] : null,
            ],
        ];
    }
}
