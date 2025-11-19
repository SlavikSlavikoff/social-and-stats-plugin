<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Plugin\InspiratoStats\Database\Factories\TimelineCardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineCard extends Model
{
    use HasFactory;

    protected $table = 'socialprofile_timeline_cards';

    protected $fillable = [
        'timeline_id',
        'period_id',
        'type',
        'title',
        'subtitle',
        'image_path',
        'button_label',
        'button_url',
        'items',
        'position',
        'highlight',
        'is_visible',
    ];

    protected $casts = [
        'items' => 'array',
        'highlight' => 'boolean',
        'is_visible' => 'boolean',
    ];

    /**
     * @return BelongsTo<Timeline, TimelineCard>
     */
    public function timeline(): BelongsTo
    {
        return $this->belongsTo(Timeline::class);
    }

    /**
     * @return BelongsTo<TimelinePeriod, TimelineCard>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(TimelinePeriod::class, 'period_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $card) {
            $items = array_values(array_filter($card->items ?? [], static fn ($value) => $value !== null && $value !== ''));
            $card->items = array_slice($items, 0, 5);

            if ($card->timeline_id) {
                $timeline = $card->relationLoaded('timeline')
                    ? $card->timeline
                    : Timeline::find($card->timeline_id);

                if ($timeline !== null) {
                    $card->type = $timeline->type;
                }
            }
        });
    }

    protected static function newFactory(): TimelineCardFactory
    {
        return TimelineCardFactory::new();
    }
}
