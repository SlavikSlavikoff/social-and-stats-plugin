<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Events\ActivityChanged;
use Azuriom\Plugin\InspiratoStats\Events\CoinsChanged;
use Azuriom\Plugin\InspiratoStats\Events\SocialStatsUpdated;
use Azuriom\Plugin\InspiratoStats\Events\TrustLevelChanged;
use Azuriom\Plugin\InspiratoStats\Events\ViolationAdded;
use Azuriom\Plugin\InspiratoStats\Http\Requests\StoreViolationRequest;
use Azuriom\Plugin\InspiratoStats\Http\Requests\UpdateTrustLevelRequest;
use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Azuriom\Plugin\InspiratoStats\Models\GameStatistic;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
use Azuriom\Plugin\InspiratoStats\Models\Violation;
use Azuriom\Plugin\InspiratoStats\Support\ActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->string('query')->trim();

        $users = User::query()
            ->when($query->isNotEmpty(), function ($builder) use ($query) {
                $builder->where('name', 'like', '%'.$query.'%');
            })
            ->orderBy('name')
            ->paginate(20);

        $userIds = $users->pluck('id');
        $scores = SocialScore::whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $activities = ActivityPoint::whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $coins = CoinBalance::whereIn('user_id', $userIds)->get()->keyBy('user_id');

        return view('socialprofile::admin.users.index', [
            'users' => $users,
            'query' => $query,
            'scores' => $scores,
            'activities' => $activities,
            'coins' => $coins,
        ]);
    }

    public function show(User $user)
    {
        $score = SocialScore::firstOrCreate(['user_id' => $user->id]);
        $activity = ActivityPoint::firstOrCreate(['user_id' => $user->id]);
        $coins = CoinBalance::firstOrCreate(['user_id' => $user->id]);
        $stats = GameStatistic::firstOrCreate(['user_id' => $user->id]);
        $trust = TrustLevel::firstOrCreate(['user_id' => $user->id]);
        $violations = Violation::where('user_id', $user->id)->latest()->get();

        return view('socialprofile::admin.users.show', compact(
            'user',
            'score',
            'activity',
            'coins',
            'stats',
            'trust',
            'violations'
        ) + [
            'trustLevels' => TrustLevel::LEVELS,
        ]);
    }

    public function updateMetrics(Request $request, User $user)
    {
        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0'],
            'activity' => ['required', 'integer', 'min:0'],
            'balance' => ['required', 'numeric', 'min:0'],
            'hold' => ['nullable', 'numeric', 'min:0'],
            'played_minutes' => ['required', 'integer', 'min:0'],
            'kills' => ['nullable', 'integer', 'min:0'],
            'deaths' => ['nullable', 'integer', 'min:0'],
        ]);

        [$activity, $coins, $stats] = DB::transaction(function () use ($user, $validated) {
            $score = SocialScore::firstOrCreate(['user_id' => $user->id]);
            $score->update(['score' => $validated['score']]);

            $activity = ActivityPoint::firstOrCreate(['user_id' => $user->id]);
            $activity->update(['points' => $validated['activity']]);

            $coins = CoinBalance::lockForUpdate()->firstOrCreate(['user_id' => $user->id]);
            $coins->update([
                'balance' => $validated['balance'],
                'hold' => $validated['hold'] ?? null,
            ]);

            $stats = GameStatistic::firstOrCreate(['user_id' => $user->id]);
            $stats->update([
                'played_minutes' => $validated['played_minutes'],
                'kills' => $validated['kills'] ?? 0,
                'deaths' => $validated['deaths'] ?? 0,
            ]);

            return [$activity, $coins, $stats];
        });

        event(new SocialStatsUpdated($user, $stats));
        event(new ActivityChanged($user, $activity));
        event(new CoinsChanged($user, $coins));

        ActionLogger::log('socialprofile.admin.metrics.updated', [
            'user_id' => $user->id,
            'actor_id' => auth()->id(),
        ]);

        return redirect()->route('socialprofile.admin.users.show', $user)->with('status', __('socialprofile::messages.admin.users.updated'));
    }

    public function updateTrust(UpdateTrustLevelRequest $request, User $user)
    {
        $trust = TrustLevel::firstOrCreate(['user_id' => $user->id]);
        $trust->fill($request->validated());
        $trust->granted_by = auth()->id();
        $trust->save();

        event(new TrustLevelChanged($user, $trust, auth()->user()));

        ActionLogger::log('socialprofile.admin.trust.updated', [
            'user_id' => $user->id,
            'actor_id' => auth()->id(),
            'level' => $trust->level,
        ]);

        return redirect()->route('socialprofile.admin.users.show', $user)->with('status', __('socialprofile::messages.admin.users.updated'));
    }

    public function storeViolation(StoreViolationRequest $request, User $user)
    {
        $payload = $request->validated();
        $payload['user_id'] = $user->id;
        $payload['issued_by'] = auth()->id();

        $violation = Violation::create($payload);

        event(new ViolationAdded($user, $violation));

        ActionLogger::log('socialprofile.admin.violation.created', [
            'user_id' => $user->id,
            'actor_id' => auth()->id(),
            'violation_id' => $violation->id,
        ]);

        return redirect()->route('socialprofile.admin.users.show', $user)->with('status', __('socialprofile::messages.admin.users.updated'));
    }
}
