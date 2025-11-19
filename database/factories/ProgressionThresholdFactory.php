<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThreshold;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressionThreshold>
 */
class ProgressionThresholdFactory extends Factory
{
    protected $model = ProgressionThreshold::class;

    public function definition(): array
    {
        return [
            'rating_id' => ProgressionRating::factory(),
            'value' => $this->faker->numberBetween(-100, 200),
            'label' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
            'icon' => 'heroicon-o-star',
            'direction' => $this->faker->randomElement([
                ProgressionThreshold::DIRECTION_ASCEND,
                ProgressionThreshold::DIRECTION_DESCEND,
                ProgressionThreshold::DIRECTION_ANY,
            ]),
            'is_punishment' => false,
            'is_major' => $this->faker->boolean(),
            'position' => $this->faker->numberBetween(0, 10),
            'band_min' => null,
            'band_max' => null,
            'metadata' => ['tooltip_alignment' => 'center'],
        ];
    }
}

