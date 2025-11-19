<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProgressionRating>
 */
class ProgressionRatingFactory extends Factory
{
    protected $model = ProgressionRating::class;

    public function definition(): array
    {
        return [
            'slug' => Str::slug($this->faker->unique()->words(2, true)),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'type' => ProgressionRating::TYPE_CUSTOM,
            'is_enabled' => true,
            'scale_min' => -100,
            'scale_max' => 100,
            'settings' => [
                'color' => $this->faker->hexColor(),
                'unit' => null,
                'display_zero' => 0,
                'support_threshold' => 1000,
                'support_meta_key' => 'support_points',
                'visual_min' => null,
                'visual_max' => null,
            ],
            'sort_order' => 1,
        ];
    }

    public function social(): self
    {
        return $this->state(fn () => [
            'slug' => 'social',
            'type' => ProgressionRating::TYPE_SOCIAL,
        ]);
    }

    public function activity(): self
    {
        return $this->state(fn () => [
            'slug' => 'activity',
            'type' => ProgressionRating::TYPE_ACTIVITY,
        ]);
    }
}
