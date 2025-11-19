<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Plugin\InspiratoStats\Database\Factories\ProgressionThresholdFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgressionThreshold extends Model
{
    use HasFactory;

    public const DIRECTION_ASCEND = 'ascend';
    public const DIRECTION_DESCEND = 'descend';
    public const DIRECTION_ANY = 'any';

    protected $table = 'socialprofile_rating_thresholds';

    protected $fillable = [
        'rating_id',
        'value',
        'label',
        'description',
        'color',
        'icon',
        'direction',
        'is_punishment',
        'is_major',
        'position',
        'band_min',
        'band_max',
        'metadata',
    ];

    protected $casts = [
        'value' => 'integer',
        'is_punishment' => 'boolean',
        'is_major' => 'boolean',
        'position' => 'integer',
        'band_min' => 'integer',
        'band_max' => 'integer',
        'metadata' => 'array',
    ];

    public function rating(): BelongsTo
    {
        return $this->belongsTo(ProgressionRating::class, 'rating_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ProgressionThresholdAction::class, 'threshold_id');
    }

    public function userStates(): HasMany
    {
        return $this->hasMany(ProgressionUserThreshold::class, 'threshold_id');
    }

    public function isPunishment(): bool
    {
        return (bool) $this->is_punishment;
    }

    /**
     * @return array{min: int|null, max: int|null}
     */
    public function band(): array
    {
        $min = $this->band_min;
        $max = $this->band_max;

        if ($min === null && $max === null) {
            return $this->inferBandFromDirection();
        }

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        return ['min' => $min, 'max' => $max];
    }

    public function valueWithinBand(int $value): bool
    {
        $band = $this->band();

        if ($band['min'] !== null && $value < $band['min']) {
            return false;
        }

        if ($band['max'] !== null && $value > $band['max']) {
            return false;
        }

        return true;
    }

    /**
     * @return array{min: int|null, max: int|null}
     */
    protected function inferBandFromDirection(): array
    {
        return match ($this->direction) {
            self::DIRECTION_ASCEND => ['min' => $this->value, 'max' => null],
            self::DIRECTION_DESCEND => ['min' => null, 'max' => $this->value],
            default => ['min' => $this->value, 'max' => $this->value],
        };
    }

    protected static function newFactory(): ProgressionThresholdFactory
    {
        return ProgressionThresholdFactory::new();
    }
}
