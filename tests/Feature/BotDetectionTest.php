<?php

namespace Tests\Feature;

use App\Models\BotEvent;
use App\Models\LinkClick;
use App\Models\ShortLink;
use App\Models\User;
use App\Support\ShortLinkAnalytics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BotDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        Cache::flush();

        parent::tearDown();
    }

    public function test_human_redirect_still_logs_link_clicks(): void
    {
        $link = ShortLink::factory()->create([
            'slug' => 'humanhit',
            'destination_url' => 'https://example.com',
            'is_active' => true,
        ]);

        $this->withHeader(
            'User-Agent',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        )->get('/humanhit')->assertRedirect('https://example.com/');

        $this->assertSame(1, LinkClick::query()->count());
        $this->assertSame(0, BotEvent::query()->count());
    }

    public function test_human_redirect_with_robot_in_product_name_logs_clicks_not_bot_events(): void
    {
        ShortLink::factory()->create([
            'slug' => 'robotname',
            'destination_url' => 'https://example.com/rn',
            'is_active' => true,
        ]);

        $this->withHeader('User-Agent', 'AcmeRobotBrowser/1.0 (Windows NT 10.0)')
            ->get('/robotname')
            ->assertRedirect('https://example.com/rn');

        $this->assertSame(1, LinkClick::query()->count());
        $this->assertSame(0, BotEvent::query()->count());
    }

    public function test_blank_user_agent_redirect_is_automated_and_persists_missing_user_agent_reason(): void
    {
        $link = ShortLink::factory()->create([
            'slug' => 'noua',
            'destination_url' => 'https://example.com/noua',
            'is_active' => true,
        ]);

        $this->withHeader('User-Agent', '')
            ->get('/noua')
            ->assertRedirect('https://example.com/noua');

        $this->assertSame(0, LinkClick::query()->count());
        $this->assertSame(1, BotEvent::query()->count());
        $this->assertDatabaseHas('bot_events', [
            'short_link_id' => $link->id,
            'endpoint' => 'redirect',
            'reason' => 'missing_user_agent',
            'user_agent' => null,
        ]);
    }

    public function test_blank_user_agent_resolver_is_automated_and_persists_missing_user_agent_reason(): void
    {
        config(['app.url' => 'https://resolver-noua.test']);

        $user = User::factory()->create();
        ShortLink::factory()->for($user)->create([
            'slug' => 'rnoua',
            'destination_url' => 'https://dest.example/rnoua',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withHeader('User-Agent', '')
            ->postJson(route('links.expand.submit'), [
                'url' => 'https://resolver-noua.test/rnoua',
            ])
            ->assertOk()
            ->assertJsonPath('was_internal', true);

        $this->assertSame(0, LinkClick::query()->count());
        $this->assertSame(1, BotEvent::query()->count());
        $this->assertDatabaseHas('bot_events', [
            'endpoint' => 'resolver',
            'reason' => 'missing_user_agent',
            'user_agent' => null,
        ]);
    }

    public function test_filtered_bot_events_last_30_days_includes_window_start_and_excludes_just_before(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-14 12:00:00', 'UTC'));

        $link = ShortLink::factory()->create();
        $windowStart = CarbonImmutable::now()->subDays(29)->startOfDay();

        BotEvent::factory()->create([
            'short_link_id' => $link->id,
            'created_at' => $windowStart,
        ]);

        BotEvent::factory()->create([
            'short_link_id' => $link->id,
            'created_at' => $windowStart->subSecond(),
        ]);

        $this->assertSame(1, ShortLinkAnalytics::filteredBotEventsLast30Days($link->id));
    }

    public function test_known_bot_redirect_creates_bot_events_and_not_link_clicks(): void
    {
        $link = ShortLink::factory()->create([
            'slug' => 'botpath',
            'destination_url' => 'https://example.com/bot',
            'is_active' => true,
        ]);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
            ->get('/botpath')
            ->assertRedirect('https://example.com/bot');

        $this->assertSame(0, LinkClick::query()->count());
        $this->assertSame(1, BotEvent::query()->count());
        $this->assertDatabaseHas('bot_events', [
            'short_link_id' => $link->id,
            'endpoint' => 'redirect',
            'reason' => 'known_bot_signature',
        ]);
    }

    public function test_abusive_repeated_redirect_bot_traffic_can_return_429(): void
    {
        config([
            'bot-detection.redirect.max_bot_attempts_per_window' => 2,
            'bot-detection.redirect.decay_seconds' => 60,
        ]);

        ShortLink::factory()->create([
            'slug' => 'hammer',
            'destination_url' => 'https://example.com',
            'is_active' => true,
        ]);

        $ua = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

        $this->withHeader('User-Agent', $ua)->get('/hammer')->assertRedirect();
        $this->withHeader('User-Agent', $ua)->get('/hammer')->assertRedirect();

        $this->withHeader('User-Agent', $ua)->get('/hammer')->assertStatus(429);

        $this->assertSame(0, LinkClick::query()->count());
        $this->assertSame(3, BotEvent::query()->count());
        $this->assertSame(1, BotEvent::query()->where('reason', 'rate_limited')->count());
    }

    public function test_resolver_bot_traffic_records_bot_events_and_no_link_clicks(): void
    {
        config(['app.url' => 'https://resolver-bot.test']);

        $user = User::factory()->create();
        ShortLink::factory()->for($user)->create([
            'slug' => 'resolveme',
            'destination_url' => 'https://dest.example/from-resolver',
            'is_active' => true,
        ]);

        $this->assertSame(0, LinkClick::query()->count());

        $this->actingAs($user)
            ->withHeader('User-Agent', 'curl/8.5.0')
            ->postJson(route('links.expand.submit'), [
                'url' => 'https://resolver-bot.test/resolveme',
            ])
            ->assertOk()
            ->assertJsonPath('was_internal', true);

        $this->assertSame(0, LinkClick::query()->count());
        $this->assertSame(1, BotEvent::query()->count());
        $this->assertDatabaseHas('bot_events', [
            'endpoint' => 'resolver',
            'reason' => 'known_bot_signature',
        ]);
    }

    public function test_resolver_abusive_automation_can_return_429(): void
    {
        config([
            'app.url' => 'https://resolver-rate.test',
            'bot-detection.resolver.max_bot_attempts_per_window' => 2,
            'bot-detection.resolver.decay_seconds' => 60,
        ]);

        $user = User::factory()->create();
        ShortLink::factory()->for($user)->create([
            'slug' => 'r1',
            'destination_url' => 'https://dest.example/a',
            'is_active' => true,
        ]);

        $ua = 'curl/8.5.0';
        $payload = ['url' => 'https://resolver-rate.test/r1'];

        $this->actingAs($user)->withHeader('User-Agent', $ua)->postJson(route('links.expand.submit'), $payload)->assertOk();
        $this->actingAs($user)->withHeader('User-Agent', $ua)->postJson(route('links.expand.submit'), $payload)->assertOk();

        $this->actingAs($user)->withHeader('User-Agent', $ua)->postJson(route('links.expand.submit'), $payload)->assertStatus(429);

        $this->assertSame(0, LinkClick::query()->count());
        $this->assertGreaterThanOrEqual(1, BotEvent::query()->where('reason', 'rate_limited')->count());
    }

    public function test_dashboard_and_link_analytics_remain_human_only_when_bot_events_exist(): void
    {
        $user = User::factory()->create();
        $link = ShortLink::factory()->for($user)->create(['slug' => 'mixed']);

        LinkClick::factory()->count(2)->create(['short_link_id' => $link->id]);
        BotEvent::factory()->count(3)->create([
            'short_link_id' => $link->id,
            'endpoint' => 'redirect',
            'reason' => 'known_bot_signature',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.total_clicks', 2)
                ->where('summary.filtered_bot_events_30d', 3),
            );

        $this->actingAs($user)
            ->get(route('links.show', $link))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Links/Show')
                ->where('analytics.total_clicks', 2)
                ->where('analytics.filtered_bot_events_30d', 3),
            );
    }
}
