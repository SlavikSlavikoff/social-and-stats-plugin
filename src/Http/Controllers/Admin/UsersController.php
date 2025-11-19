<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Events\ActivityChanged;
use Azuriom\Plugin\InspiratoStats\Events\CoinsChanged;
use Azuriom\Plugin\InspiratoStats\Events\SocialScoreChanged;
use Azuriom\Plugin\InspiratoStats\Events\SocialStatsUpdated;
use Azuriom\Plugin\InspiratoStats\Events\TrustLevelChanged;
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

        [$score, $activity, $coins, $stats, $deltas] = DB::transaction(function () use ($user, $validated) {
            $score = SocialScore::lockForUpdate()->firstOrCreate(['user_id' => $user->id]);
            $scoreBefore = (int) $score->score;
            $score->update(['score' => $validated['score']]);

            $activity = ActivityPoint::lockForUpdate()->firstOrCreate(['user_id' => $user->id]);
            $activityBefore = (int) $activity->points;
            $activity->update(['points' => $validated['activity']]);

            $coins = CoinBalance::lockForUpdate()->firstOrCreate(['user_id' => $user->id]);
            $coinsBefore = (float) $coins->balance;
            $coins->update([
                'balance' => $validated['balance'],
                'hold' => $validated['hold'] ?? null,
            ]);

            $stats = GameStatistic::lockForUpdate()->firstOrCreate(['user_id' => $user->id]);
            $statsBefore = $stats->only(['played_minutes', 'kills', 'deaths']);
            $stats->update([
                'played_minutes' => $validated['played_minutes'],
                'kills' => $validated['kills'] ?? 0,
                'deaths' => $validated['deaths'] ?? 0,
            ]);

            return [
                $score,
                $activity,
                $coins,
                $stats,
                [
                    'score' => $validated['score'] - $scoreBefore,
                    'activity' => $validated['activity'] - $activityBefore,
                    'coins' => $validated['balance'] - $coinsBefore,
                    'stats' => [
                        'played_minutes' => $validated['played_minutes'] - ($statsBefore['played_minutes'] ?? 0),
                        'kills' => ($validated['kills'] ?? 0) - ($statsBefore['kills'] ?? 0),
                        'deaths' => ($validated['deaths'] ?? 0) - ($statsBefore['deaths'] ?? 0),
                    ],
                ],
            ];
        });

        event(new SocialScoreChanged($user, $score, [
            'delta' => $deltas['score'],
            'source' => 'admin.manual',
        ]));

        event(new SocialStatsUpdated($user, $stats, [
            'values' => $stats->only(['played_minutes', 'kills', 'deaths']),
            'delta' => $deltas['stats'],
            'source' => 'admin.manual',
        ]));

        event(new ActivityChanged($user, $activity, [
            'delta' => $deltas['activity'],
            'source' => 'admin.manual',
        ]));

        event(new CoinsChanged($user, $coins, [
            'delta' => $deltas['coins'],
            'source' => 'admin.manual',
        ]));

        ActionLogger::log('socialprofile.admin.metrics.updated', [
            'user_id' => $user->id,
            'actor_id' => auth()->id(),
        ]);

        return redirect()->route('socialprofile.admin.users.show', $user)->with('status', __('socialprofile::messages.admin.users.updated'));
    }

    public function updateTrust(UpdateTrustLevelRequest $request, User $user)
    {
        $trust = TrustLevel::firstOrCreate(['user_id' => $user->id]);
        $oldLevel = $trust->level ?? TrustLevel::LEVELS[0];
        $trust->fill($request->validated());
        $trust->granted_by = auth()->id();
        $trust->save();

        event(new TrustLevelChanged($user, $trust, $oldLevel, $trust->level, auth()->user(), [
            'source' => 'admin.manual',
        ]));

        ActionLogger::log('socialprofile.admin.trust.updated', [
            'user_id' => $user->id,
            'actor_id' => auth()->id(),
            'level' => $trust->level,
        ]);

        return redirect()->route('socialprofile.admin.users.show', $user)->with('status', __('socialprofile::messages.admin.users.updated'));
    }

}
