<?php

namespace Tests\Feature;

use App\Models\LinkClick;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShortLinkPhase2Test extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_link_analytics_page(): void
    {
        $user = User::factory()->create();
        $link = ShortLink::factory()->for($user)->create(['slug' => 'stats']);

        LinkClick::factory()->count(2)->create([
            'short_link_id' => $link->id,
            'device_type' => 'mobile',
            'browser' => 'Safari',
            'os' => 'iOS',
        ]);

        $response = $this->actingAs($user)->get(route('links.show', $link));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Links/Show')
            ->where('shortLink.slug', 'stats')
            ->where('analytics.total_clicks', 2)
            ->has('analytics.clicks_by_day')
            ->has('analytics.by_device', 1)
            ->has('analytics.by_browser', 1)
            ->has('analytics.by_os', 1)
            ->has('analytics.by_country'),
        );
    }

    public function test_guest_cannot_view_link_analytics(): void
    {
        $link = ShortLink::factory()->create();

        $response = $this->get(route('links.show', $link));

        $response->assertRedirect(route('login'));
    }

    public function test_other_user_cannot_view_link_analytics(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $link = ShortLink::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->get(route('links.show', $link));

        $response->assertForbidden();
    }

    public function test_analytics_clicks_by_day_reflects_created_at(): void
    {
        $user = User::factory()->create();
        $link = ShortLink::factory()->for($user)->create();

        LinkClick::query()->create([
            'short_link_id' => $link->id,
            'ip_hash' => 'x',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'os' => 'Windows',
            'referrer' => null,
            'created_at' => now()->startOfDay(),
        ]);

        $this->actingAs($user)
            ->get(route('links.show', $link))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Links/Show')
                ->where('analytics.total_clicks', 1)
                ->has('analytics.clicks_by_day', fn (Assert $day) => $day
                    ->etc(),
                ),
            );
    }
}
