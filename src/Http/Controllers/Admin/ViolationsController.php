<?php

namespace Azuriom\Plugin\SocialProfile\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\User;
use Azuriom\Plugin\SocialProfile\Events\ViolationAdded;
use Azuriom\Plugin\SocialProfile\Http\Requests\StoreViolationRequest;
use Azuriom\Plugin\SocialProfile\Models\Violation;
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

    public function store(StoreViolationRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['issued_by'] = auth()->id();

        $violation = Violation::create($payload);
        $user = User::find($payload['user_id']);

        if ($user !== null) {
            event(new ViolationAdded($user, $violation));
        }

        if (function_exists('action')) {
            action()->log('socialprofile.admin.violation.created', [
                'user_id' => $payload['user_id'],
                'actor_id' => auth()->id(),
            ]);
        }

        return back()->with('status', __('socialprofile::messages.admin.violations.created'));
    }

    public function destroy(Violation $violation): RedirectResponse
    {
        $violation->delete();

        if (function_exists('action')) {
            action()->log('socialprofile.admin.violation.deleted', [
                'violation_id' => $violation->id,
                'actor_id' => auth()->id(),
            ]);
        }

        return back()->with('status', __('socialprofile::messages.admin.violations.deleted'));
    }
}
