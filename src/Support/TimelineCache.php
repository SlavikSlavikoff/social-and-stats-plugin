<?php

namespace Azuriom\Plugin\InspiratoStats\Support;

use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Closure;
use Illuminate\Support\Facades\Cache;

class TimelineCache
{
    public static function key(string $type): string
    {
        return 'socialprofile:timeline:'.$type;
    }

    /**
     * @template T
     *
     * @param Closure():T $callback
     * @return T
     */
    public static function remember(string $type, Closure $callback)
    {
        $ttl = now()->addMinutes(10);

        return Cache::remember(self::key($type), $ttl, $callback);
    }

    public static function forgetForTimeline(Timeline $timeline): void
    {
        if (! empty($timeline->type)) {
            Cache::forget(self::key($timeline->type));
        }
    }

    public static function forget(string $type): void
    {
        Cache::forget(self::key($type));
    }
}
