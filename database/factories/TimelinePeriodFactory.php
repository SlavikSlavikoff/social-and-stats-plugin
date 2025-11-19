<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Azuriom\Plugin\InspiratoStats\Models\TimelinePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimelinePeriod>
 */
class TimelinePeriodFactory extends Factory
{
    protected $model = TimelinePeriod::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-2 years', 'now');
        $end = (clone $start)->modify('+3 months');

        return [
            'timeline_id' => Timeline::factory(),
            'title' => 'Period '.$this->faker->unique()->numberBetween(1, 50),
            'description' => $this->faker->sentence(),
            'start_date' => $start,
            'end_date' => $end,
            'position' => $this->faker->numberBetween(1, 10),
        ];
    }
}
