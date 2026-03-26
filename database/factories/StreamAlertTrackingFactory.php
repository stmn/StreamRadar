<?php

namespace Database\Factories;

use App\Models\AlertRule;
use App\Models\StreamAlertTracking;
use Illuminate\Database\Eloquent\Factories\Factory;

class StreamAlertTrackingFactory extends Factory
{
    protected $model = StreamAlertTracking::class;

    public function definition(): array
    {
        return [
            'alert_rule_id' => AlertRule::factory(),
            'stream_twitch_id' => (string) fake()->unique()->numberBetween(100000, 9999999),
            'streamer_login' => fake()->userName(),
            'triggered_at' => now(),
        ];
    }

    public function seeded(): static
    {
        return $this->state(['triggered_at' => null]);
    }
}
