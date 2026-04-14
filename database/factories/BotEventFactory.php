<?php

namespace Database\Factories;

use App\Models\BotEvent;
use App\Models\ShortLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BotEvent>
 */
class BotEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'short_link_id' => ShortLink::factory(),
            'endpoint' => 'redirect',
            'action' => null,
            'reason' => 'known_bot_signature',
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
            'referrer' => null,
            'device_type' => 'desktop',
            'browser' => 'Other',
            'os' => 'Linux',
            'country_code' => null,
            'region' => null,
            'city' => null,
            'created_at' => now(),
        ];
    }
}
