<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Plugin\InspiratoStats\Database\Factories\TimelineFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timeline extends Model
{
    use HasFactory;

    public const TYPE_SEASON_HISTORY = 'season-history';
    public const TYPE_ROAD_MAP = 'road-map';

    public const TYPES = [
        self::TYPE_SEASON_HISTORY,
        self::TYPE_ROAD_MAP,
    ];

    protected $table = 'socialprofile_timelines';

    protected $fillable = [
        'type',
        'slug',
        'title',
        'subtitle',
        'intro_text',
        'is_active',
        'show_period_labels',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_period_labels' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $timeline) {
            if (empty($timeline->slug)) {
                $timeline->slug = $timeline->type;
            }
        });
    }

    /**
     * @return HasMany<TimelinePeriod>
     */
    public function periods(): HasMany
    {
        return $this->hasMany(TimelinePeriod::class)->orderBy('position');
    }

    /**
     * @return HasMany<TimelineCard>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(TimelineCard::class)->orderBy('position');
    }

    public function scopeType(Builder $builder, string $type): Builder
    {
        return $builder->where('type', $type);
    }

    public function scopeActive(Builder $builder): Builder
    {
        return $builder->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function newFactory(): TimelineFactory
    {
        return TimelineFactory::new();
    }
}
