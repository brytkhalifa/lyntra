<?php

namespace Tests\Feature;

use App\Models\LinkClick;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('summary.total_links', 0)
            ->where('summary.total_clicks', 0)
            ->where('summary.clicks_last_30_days', 0)
            ->where('summary.active_links', 0)
            ->where('summary.filtered_bot_events_30d', 0)
            ->has('clicks_by_day', 30)
            ->has('top_links', 0)
            ->has('recent_links', 0),
        );
    }

    public function test_dashboard_summary_and_lists_reflect_only_authenticated_users_links(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $linkA = ShortLink::factory()->for($owner)->create(['slug' => 'mine-a']);
        $linkB = ShortLink::factory()->for($owner)->create(['slug' => 'mine-b']);
        ShortLink::factory()->for($other)->create(['slug' => 'theirs']);

        LinkClick::factory()->count(3)->create(['short_link_id' => $linkA->id]);
        LinkClick::factory()->count(2)->create(['short_link_id' => $linkB->id]);
        LinkClick::factory()->count(10)->create([
            'short_link_id' => ShortLink::factory()->for($other)->create()->id,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.total_links', 2)
                ->where('summary.total_clicks', 5)
                ->where('summary.active_links', 2)
                ->where('summary.filtered_bot_events_30d', 0)
                ->has('clicks_by_day', 30)
                ->has('top_links', 2)
                ->where('top_links.0.slug', 'mine-a')
                ->where('top_links.0.clicks_count', 3)
                ->where('top_links.1.slug', 'mine-b')
                ->where('top_links.1.clicks_count', 2)
                ->has('recent_links', 2)
                ->where('recent_links.0.slug', 'mine-b')
                ->where('recent_links.1.slug', 'mine-a'),
            );
    }
}
