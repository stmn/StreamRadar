<?php

namespace Database\Factories;

use App\Models\BlacklistRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlacklistRuleFactory extends Factory
{
    protected $model = BlacklistRule::class;

    public function definition(): array
    {
        return [
            'type' => 'channel',
            'value' => strtolower(fake()->unique()->userName()),
            'twitch_user_id' => null,
            'profile_image_url' => null,
        ];
    }

    public function channel(string $login = null): static
    {
        return $this->state(fn () => [
            'type' => 'channel',
            'value' => $login ?? strtolower(fake()->unique()->userName()),
        ]);
    }

    public function keyword(string $keyword = null): static
    {
        return $this->state(fn () => [
            'type' => 'keyword',
            'value' => $keyword ?? fake()->unique()->word(),
        ]);
    }

    public function tag(string $tag = null): static
    {
        return $this->state(fn () => [
            'type' => 'tag',
            'value' => $tag ?? fake()->unique()->word(),
        ]);
    }
}
