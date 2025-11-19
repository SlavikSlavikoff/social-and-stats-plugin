<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Plugin\InspiratoStats\Database\Factories\TimelinePeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimelinePeriod extends Model
{
    use HasFactory;

    protected $table = 'socialprofile_timeline_periods';

    protected $fillable = [
        'timeline_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'position',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * @return BelongsTo<Timeline, TimelinePeriod>
     */
    public function timeline(): BelongsTo
    {
        return $this->belongsTo(Timeline::class);
    }

    /**
     * @return HasMany<TimelineCard>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(TimelineCard::class, 'period_id')->orderBy('position');
    }

    public function getLabelAttribute(): string
    {
        $label = $this->title;

        if ($this->start_date && $this->end_date) {
            $label .= sprintf(' (%s - %s)', $this->start_date->format('Y-m-d'), $this->end_date->format('Y-m-d'));
        } elseif ($this->start_date) {
            $label .= sprintf(' (%s)', $this->start_date->format('Y-m-d'));
        }

        return $label;
    }

    protected static function newFactory(): TimelinePeriodFactory
    {
        return TimelinePeriodFactory::new();
    }
}
