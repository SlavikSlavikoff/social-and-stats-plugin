<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Events\ViolationAdded;
use Azuriom\Plugin\InspiratoStats\Models\Violation;
use Azuriom\Plugin\InspiratoStats\Support\ActionLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ViolationsController extends Controller
{
    public function index(Request $request)
    {
        $violations = Violation::with(['user', 'issuer'])
            ->latest()
            ->paginate(25);

        return view('socialprofile::admin.violations.index', [
            'violations' => $violations,
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:warning,mute,ban,other'],
            'reason' => ['required', 'string', 'max:255'],
            'points' => ['nullable', 'integer', 'min:0'],
            'evidence_url' => ['nullable', 'url', 'max:255'],
        ]);

        $violation = Violation::create([
            'user_id' => $user->id,
            'type' => $validated['type'],
            'reason' => $validated['reason'],
            'points' => $validated['points'] ?? 0,
            'issued_by' => auth()->id(),
            'evidence_url' => $validated['evidence_url'] ?? null,
        ]);

        event(new ViolationAdded($user, $violation));

        ActionLogger::log('socialprofile.admin.violation.created', [
            'violation_id' => $violation->id,
            'user_id' => $user->id,
            'actor_id' => auth()->id(),
        ]);

        return redirect()->route('socialprofile.admin.users.show', $user)
            ->with('status', __('socialprofile::messages.admin.violations.created'));
    }

    public function destroy(Violation $violation): RedirectResponse
    {
        $violation->delete();

        ActionLogger::log('socialprofile.admin.violation.deleted', [
            'violation_id' => $violation->id,
            'actor_id' => auth()->id(),
        ]);

        return back()->with('status', __('socialprofile::messages.admin.violations.deleted'));
    }
}
