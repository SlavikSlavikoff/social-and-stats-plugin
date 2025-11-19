<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Timeline>
 */
class TimelineFactory extends Factory
{
    protected $model = Timeline::class;

    public function definition(): array
    {
        return [
            'type' => Timeline::TYPE_SEASON_HISTORY,
            'slug' => Timeline::TYPE_SEASON_HISTORY,
            'title' => $this->faker->sentence(3),
            'subtitle' => $this->faker->sentence(),
            'intro_text' => $this->faker->paragraph(),
            'is_active' => true,
            'show_period_labels' => true,
            'meta_title' => $this->faker->sentence(),
            'meta_description' => $this->faker->paragraph(),
        ];
    }

    public function roadMap(): self
    {
        return $this->state(fn () => [
            'type' => Timeline::TYPE_ROAD_MAP,
            'slug' => Timeline::TYPE_ROAD_MAP,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
