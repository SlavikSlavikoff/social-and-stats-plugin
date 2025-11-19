<?php

namespace Azuriom\Plugin\InspiratoStats\Support\Progression;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThresholdAction;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionUserThreshold;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

class ProgressionGate
{
    protected const CACHE_PREFIX = 'socialprofile.progression';
    protected const CACHE_TTL_MINUTES = 5;

    public function __construct(protected CacheRepository $cache)
    {
    }

    /**
     * Determine if the user currently has a synthetic permission granted by progression.
     */
    public function hasPermission(User $user, string $permission): ?bool
    {
        $permissions = $this->cachedPermissions($user);

        if (isset($permissions['revoked'][$permission])) {
            return false;
        }

        if (isset($permissions['granted'][$permission])) {
            return true;
        }

        return null;
    }

    /**
     * Determine if a feature flag is enabled for the user.
     */
    public function canUseFeature(User $user, string $feature): bool
    {
        $features = $this->cachedFeatures($user);

        return isset($features['enabled'][$feature]);
    }

    /**
     * @return array<int, string>
     */
    public function grantedPermissions(User $user): array
    {
        $permissions = $this->cachedPermissions($user);

        return array_keys($permissions['granted']);
    }

    /**
     * Flush the cached capability map for the given user.
     */
    public function flushUserCache(int $userId): void
    {
        $this->cache->forget($this->cacheKey($userId, 'permissions'));
        $this->cache->forget($this->cacheKey($userId, 'features'));
    }

    /**
     * @return array<string, array<string, bool>>
     */
    protected function cachedPermissions(User $user): array
    {
        return $this->cache->remember(
            $this->cacheKey($user->id, 'permissions'),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => $this->aggregatePermissions($user)
        );
    }

    /**
     * @return array<string, array<string, bool>>
     */
    protected function cachedFeatures(User $user): array
    {
        return $this->cache->remember(
            $this->cacheKey($user->id, 'features'),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => $this->aggregateFeatures($user)
        );
    }

    /**
     * @return array<string, array<string, bool>>
     */
    protected function aggregatePermissions(User $user): array
    {
        $states = $this->loadThresholdStates($user);
        $granted = [];
        $revoked = [];

        foreach ($states as $state) {
            foreach ($state->threshold->actions as $action) {
                if ($action->action === ProgressionThresholdAction::ACTION_PERMISSION_GRANT) {
                    $permission = $action->config['permission'] ?? null;

                    if ($permission !== null) {
                        unset($revoked[$permission]);
                        $granted[$permission] = true;
                    }
                } elseif ($action->action === ProgressionThresholdAction::ACTION_PERMISSION_REVOKE) {
                    $permission = $action->config['permission'] ?? null;

                    if ($permission !== null) {
                        unset($granted[$permission]);
                        $revoked[$permission] = true;
                    }
                }
            }
        }

        return [
            'granted' => $granted,
            'revoked' => $revoked,
        ];
    }

    /**
     * @return array<string, array<string, bool>>
     */
    protected function aggregateFeatures(User $user): array
    {
        $states = $this->loadThresholdStates($user);
        $enabled = [];

        foreach ($states as $state) {
            foreach ($state->threshold->actions as $action) {
                $feature = $action->config['feature'] ?? null;

                if ($feature === null) {
                    continue;
                }

                if ($action->action === ProgressionThresholdAction::ACTION_FEATURE_ENABLE) {
                    $enabled[$feature] = true;
                } elseif ($action->action === ProgressionThresholdAction::ACTION_FEATURE_DISABLE) {
                    unset($enabled[$feature]);
                }
            }
        }

        return ['enabled' => $enabled];
    }

    /**
     * @return Collection<int, ProgressionUserThreshold>
     */
    protected function loadThresholdStates(User $user): Collection
    {
        return ProgressionUserThreshold::query()
            ->where('user_id', $user->id)
            ->where('action_state', 'applied')
            ->orderBy('reached_at')
            ->with(['threshold.actions'])
            ->get();
    }

    protected function cacheKey(int $userId, string $suffix): string
    {
        return sprintf('%s.%d.%s', self::CACHE_PREFIX, $userId, $suffix);
    }
}
