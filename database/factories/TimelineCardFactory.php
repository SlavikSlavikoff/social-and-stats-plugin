<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Factories;

use Azuriom\Plugin\InspiratoStats\Models\Timeline;
use Azuriom\Plugin\InspiratoStats\Models\TimelineCard;
use Azuriom\Plugin\InspiratoStats\Models\TimelinePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimelineCard>
 */
class TimelineCardFactory extends Factory
{
    protected $model = TimelineCard::class;

    public function definition(): array
    {
        return [
            'timeline_id' => null,
            'period_id' => TimelinePeriod::factory(),
            'type' => null,
            'title' => $this->faker->sentence(3),
            'subtitle' => $this->faker->sentence(),
            'image_path' => null,
            'button_label' => $this->faker->optional()->words(2, true),
            'button_url' => $this->faker->optional()->url(),
            'items' => $this->faker->sentences($this->faker->numberBetween(1, 5)),
            'position' => $this->faker->numberBetween(1, 10),
            'highlight' => $this->faker->boolean(20),
            'is_visible' => true,
        ];
    }

    public function hidden(): self
    {
        return $this->state(fn () => ['is_visible' => false]);
    }

    public function configure(): static
    {
        $resolver = function (TimelineCard $card): ?TimelinePeriod {
            if ($card->relationLoaded('period') && $card->period !== null) {
                return $card->period;
            }

            if ($card->period instanceof TimelinePeriod) {
                return $card->period;
            }

            if ($card->period_id instanceof TimelinePeriod) {
                return $card->period_id;
            }

            if ($card->period_id) {
                return TimelinePeriod::query()->find($card->period_id);
            }

            return null;
        };

        return $this->afterMaking(function (TimelineCard $card) use ($resolver) {
            $period = $resolver($card);

            if ($period !== null) {
                $card->timeline_id ??= $period->timeline_id;
                $card->type ??= $period->timeline?->type ?? Timeline::TYPE_SEASON_HISTORY;
            }
        })->afterCreating(function (TimelineCard $card) use ($resolver) {
            $period = $resolver($card);

            if ($period !== null) {
                $updated = false;

                if (! $card->timeline_id) {
                    $card->timeline_id = $period->timeline_id;
                    $updated = true;
                }

                if (! $card->type) {
                    $card->type = $period->timeline?->type ?? Timeline::TYPE_SEASON_HISTORY;
                    $updated = true;
                }

                if ($updated) {
                    $card->save();
                }
            }
        });
    }
}
