<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Database\Factories\ProgressionRatingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property bool $is_enabled
 * @property int $scale_min
 * @property int $scale_max
 * @property array|null $settings
 * @property int $sort_order
 *
 * @property-read Collection<int, UserRatingValue> $userValues
 * @property-read Collection<int, ProgressionThreshold> $thresholds
 * @property-read Collection<int, ProgressionRule> $rules
 */
class ProgressionRating extends Model
{
    use HasFactory;

    public const TYPE_SOCIAL = 'social';
    public const TYPE_ACTIVITY = 'activity';
    public const TYPE_CUSTOM = 'custom';

    protected $table = 'socialprofile_ratings';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'type',
        'is_enabled',
        'scale_min',
        'scale_max',
        'settings',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'scale_min' => 'integer',
        'scale_max' => 'integer',
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProgressionRating $rating) {
            if (empty($rating->slug)) {
                $rating->slug = Str::slug($rating->name ?: Str::random(5));
            }

            if ($rating->sort_order === null) {
                $rating->sort_order = (int) (static::max('sort_order') ?? 0) + 1;
            }
        });
    }

    /**
     * @return Builder<static>
     */
    public function scopeEnabled(Builder $builder): Builder
    {
        return $builder->where('is_enabled', true);
    }

    public function userValues(): HasMany
    {
        return $this->hasMany(UserRatingValue::class, 'rating_id');
    }

    public function thresholds(): HasMany
    {
        return $this->hasMany(ProgressionThreshold::class, 'rating_id')->orderBy('position');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(ProgressionRule::class, 'rating_id');
    }

    public function defaultSettings(): array
    {
        return [
            'color' => $this->settings['color'] ?? '#2b6cb0',
            'unit' => $this->settings['unit'] ?? null,
            'display_zero' => $this->settings['display_zero'] ?? 0,
        ];
    }

    /**
     * @return array{0:int,1:int}
     */
    public function visualBounds(): array
    {
        $defaults = match ($this->type) {
            self::TYPE_SOCIAL => [-100, 1000],
            self::TYPE_ACTIVITY => [0, 100000],
            default => [$this->scale_min, $this->scale_max],
        };

        $settings = $this->settings ?? [];
        $visualMin = array_key_exists('visual_min', $settings) && $settings['visual_min'] !== null
            ? (int) $settings['visual_min']
            : $defaults[0];
        $visualMax = array_key_exists('visual_max', $settings) && $settings['visual_max'] !== null
            ? (int) $settings['visual_max']
            : $defaults[1];

        if ($visualMin >= $visualMax) {
            $visualMin = $this->scale_min;
            $visualMax = $this->scale_max;
        }

        return [$visualMin, $visualMax];
    }

    public function findValueForUser(User $user): ?UserRatingValue
    {
        return $this->userValues()->where('user_id', $user->id)->first();
    }

    public function findOrCreateValueForUser(User $user): UserRatingValue
    {
        return $this->userValues()->firstOrCreate(['user_id' => $user->id]);
    }

    protected static function newFactory(): ProgressionRatingFactory
    {
        return ProgressionRatingFactory::new();
    }
}
