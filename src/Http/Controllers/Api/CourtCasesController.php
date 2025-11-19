<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Api;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\CourtCase;
use Azuriom\Plugin\InspiratoStats\Support\Court\CourtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourtCasesController extends Controller
{
    public function publicIndex(Request $request): JsonResponse
    {
        $cases = CourtCase::where('visibility', 'public')
            ->with(['subject', 'judge'])
            ->latest('id')
            ->paginate(15);

        return response()->json($cases->through(fn ($case) => $this->transform($case)));
    }

    public function internalIndex(Request $request): JsonResponse
    {
        $cases = CourtCase::visibleFor($request->user())
            ->with(['subject', 'judge'])
            ->latest('id')
            ->paginate(25);

        return response()->json($cases->through(fn ($case) => $this->transform($case)));
    }

    public function show(CourtCase $case, Request $request): JsonResponse
    {
        $this->authorizeCase($case, $request);

        return response()->json($this->transform($case->load(['subject', 'judge', 'actions'])));
    }

    public function store(Request $request, CourtService $service): JsonResponse
    {
        $payload = $request->validate([
            'subject_id' => 'required|exists:users,id',
            'mode' => 'required|in:auto,manual',
            'template_key' => 'required_if:mode,auto',
            'comment' => 'nullable|string|max:5000',
            'executor' => 'nullable|in:site,discord,minecraft',
            'punishment' => 'nullable|array',
            'ban' => 'nullable|array',
            'mute' => 'nullable|array',
            'unverify' => 'nullable|boolean',
        ]);

        $subject = User::findOrFail($payload['subject_id']);
        $judge = $request->user();

        $payload['executor'] = $payload['executor'] ?? config('socialprofile.court.default_executor', 'site');

        $case = $payload['mode'] === 'auto'
            ? $service->issueFromTemplate($judge, $subject, $payload)
            : $service->issueManual($judge, $subject, $payload);

        return response()->json($this->transform($case), 201);
    }

    protected function authorizeCase(CourtCase $case, Request $request): void
    {
        if ($case->visibility === 'public') {
            return;
        }

        $user = $request->user();

        if ($user && ($user->can('social.court.judge') || $user->can('social.court.archive'))) {
            return;
        }

        abort(403);
    }

    protected function transform(CourtCase $case): array
    {
        return [
            'case_number' => $case->case_number,
            'id' => $case->id,
            'status' => $case->status,
            'mode' => $case->mode,
            'executor' => $case->executor,
            'visibility' => $case->visibility,
            'comment' => $case->comment,
            'issued_at' => optional($case->issued_at)->toIso8601String(),
            'expires_at' => optional($case->expires_at)->toIso8601String(),
            'subject' => [
                'id' => $case->subject?->id,
                'name' => $case->subject?->name,
            ],
            'judge' => [
                'id' => $case->judge?->id,
                'name' => $case->judge?->name,
            ],
        ];
    }
}
