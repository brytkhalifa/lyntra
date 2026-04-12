<?php

namespace Tests\Feature;

use App\Models\LinkClick;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShortLinkPhase1Test extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_active_link_to_destination(): void
    {
        $link = ShortLink::factory()->create([
            'destination_url' => 'https://example.com/page',
            'slug' => 'myshort',
            'is_active' => true,
            'expires_at' => null,
        ]);

        $response = $this->get('/myshort');

        $response->assertRedirect('https://example.com/page');
    }

    public function test_redirect_merges_link_utm_over_destination_query(): void
    {
        $link = ShortLink::factory()->create([
            'destination_url' => 'https://example.com/target?bar=keep&utm_source=old',
            'slug' => 'promo',
            'utm_source' => 'newsletter',
            'utm_medium' => null,
            'is_active' => true,
        ]);

        $response = $this->get('/promo');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('bar=keep', $location);
        $this->assertStringContainsString('utm_source=newsletter', $location);
        $this->assertStringNotContainsString('utm_source=old', $location);
    }

    public function test_redirect_passes_through_visitor_non_utm_query_params(): void
    {
        $link = ShortLink::factory()->create([
            'destination_url' => 'https://example.com/here',
            'slug' => 'share',
            'is_active' => true,
        ]);

        $response = $this->get('/share?ref=twitter');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('ref=twitter', $location);
    }

    public function test_expired_short_link_returns_gone(): void
    {
        $link = ShortLink::factory()->create([
            'slug' => 'gone',
            'expires_at' => now()->subMinute(),
            'is_active' => true,
        ]);

        $response = $this->get('/gone');

        $response->assertStatus(410);
        $this->assertSame(0, LinkClick::query()->count());
    }

    public function test_inactive_short_link_returns_not_found(): void
    {
        $link = ShortLink::factory()->create([
            'slug' => 'off',
            'is_active' => false,
        ]);

        $response = $this->get('/off');

        $response->assertNotFound();
    }

    public function test_unknown_slug_returns_not_found(): void
    {
        $response = $this->get('/does-not-exist-xyz');

        $response->assertNotFound();
    }

    public function test_successful_redirect_logs_click(): void
    {
        $link = ShortLink::factory()->create([
            'slug' => 'trackme',
            'destination_url' => 'https://example.com',
            'is_active' => true,
        ]);

        $response = $this->withHeader(
            'User-Agent',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36'
        )->withHeader('Referer', 'https://google.com/')
            ->get('/trackme');

        $response->assertRedirect();

        $this->assertDatabaseCount('link_clicks', 1);
        $click = LinkClick::query()->first();
        $this->assertNotNull($click);
        $this->assertSame($link->id, $click->short_link_id);
        $this->assertNotNull($click->ip_hash);
        $this->assertSame('https://google.com/', $click->referrer);
        $this->assertSame('desktop', $click->device_type);
        $this->assertSame('Chrome', $click->browser);
        $this->assertSame('Windows', $click->os);
    }

    public function test_guest_is_redirected_to_login_when_visiting_create_link_page(): void
    {
        $response = $this->get(route('links.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_create_link_page_prefills_destination_from_valid_query_string(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $url = 'https://example.com/blog/prefilled';
        $response = $this->get(route('links.create', ['destination_url' => $url]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Links/Create')
            ->where('prefill_destination_url', $url),
        );
    }

    public function test_create_link_page_ignores_invalid_destination_query_string(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('links.create', ['destination_url' => 'not-a-valid-url']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Links/Create')
            ->where('prefill_destination_url', null),
        );
    }

    public function test_guest_cannot_store_short_link(): void
    {
        $response = $this->post(route('links.store'), [
            'destination_url' => 'https://example.com',
            'slug' => 'nope',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('short_links', 0);
    }

    public function test_reserved_slug_is_rejected_when_creating(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('links.store'), [
            'destination_url' => 'https://example.com',
            'slug' => 'login',
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('slug');
        $this->assertDatabaseCount('short_links', 0);
    }

    public function test_user_cannot_update_another_users_link(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $link = ShortLink::factory()->for($owner)->create([
            'slug' => 'mine',
        ]);
        $originalDestination = $link->destination_url;

        $this->actingAs($other);

        $response = $this->from(route('links.index'))->patch(route('links.update', $link), [
            'destination_url' => 'https://evil.com',
            'slug' => 'mine',
            'is_active' => true,
        ]);

        $response->assertForbidden();
        $this->assertSame($originalDestination, $link->fresh()->destination_url);
    }

    public function test_authenticated_user_can_create_short_link_without_slug(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->from(route('links.create'))->post(route('links.store'), [
            'destination_url' => 'https://example.com/created',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('links.index'));
        $this->assertDatabaseHas('short_links', [
            'user_id' => $user->id,
            'destination_url' => 'https://example.com/created',
        ]);
        $this->assertSame(1, ShortLink::query()->where('user_id', $user->id)->count());
        $this->assertNotNull(ShortLink::query()->where('user_id', $user->id)->value('slug'));
    }
}
