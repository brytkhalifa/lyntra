<?php

namespace Tests\Feature;

use App\Models\LinkClick;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShortLinkResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config(['app.url' => 'https://resolver-app.test']);
    }

    public function test_guest_cannot_access_expand_page(): void
    {
        $this->get(route('links.expand'))
            ->assertRedirect();
    }

    public function test_guest_cannot_submit_expand(): void
    {
        $this->postJson(route('links.expand.submit'), [
            'url' => 'https://example.com',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_expand_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('links.expand'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Links/Resolve'));
    }

    public function test_internal_short_link_resolves_with_utm_and_does_not_log_click(): void
    {
        $user = User::factory()->create();
        ShortLink::factory()->create([
            'destination_url' => 'https://dest.example/page?keep=1&utm_source=old',
            'slug' => 'go',
            'utm_source' => 'email',
            'utm_medium' => 'news',
            'is_active' => true,
            'expires_at' => null,
        ]);

        $this->assertSame(0, LinkClick::query()->count());

        $response = $this->actingAs($user)->postJson(route('links.expand.submit'), [
            'url' => 'https://resolver-app.test/go',
        ]);

        $response->assertOk();
        $response->assertJson([
            'was_internal' => true,
            'redirect_count' => 0,
        ]);
        $resolved = (string) $response->json('resolved_url');
        $this->assertStringContainsString('utm_source=email', $resolved);
        $this->assertStringContainsString('utm_medium=news', $resolved);
        $this->assertStringContainsString('keep=1', $resolved);
        $this->assertStringNotContainsString('utm_source=old', $resolved);
        $this->assertSame(0, LinkClick::query()->count());
    }

    public function test_internal_chain_resolves_to_final_destination(): void
    {
        $user = User::factory()->create();
        ShortLink::factory()->create([
            'slug' => 'first',
            'destination_url' => 'https://resolver-app.test/second',
            'is_active' => true,
        ]);
        ShortLink::factory()->create([
            'slug' => 'second',
            'destination_url' => 'https://terminal.example/result',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('links.expand.submit'), [
            'url' => 'https://resolver-app.test/first',
        ]);

        $response->assertOk();
        $this->assertSame('https://terminal.example/result', $response->json('resolved_url'));
        $response->assertJson([
            'was_internal' => true,
            'redirect_count' => 0,
        ]);
    }

    public function test_external_redirect_then_internal_resolves_fully(): void
    {
        $user = User::factory()->create();
        ShortLink::factory()->create([
            'slug' => 'via',
            'destination_url' => 'https://final-dest.example/page',
            'is_active' => true,
        ]);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $method = $request->method();

            if ($url === 'https://hop.test/start' && $method === 'HEAD') {
                return Http::response('', 302, ['Location' => 'https://resolver-app.test/via']);
            }
            if ($url === 'https://final-dest.example/page' && $method === 'HEAD') {
                return Http::response('', 200);
            }

            return Http::response('unexpected', 500);
        });

        $response = $this->actingAs($user)->postJson(route('links.expand.submit'), [
            'url' => 'https://hop.test/start',
        ]);

        $response->assertOk();
        $this->assertSame('https://final-dest.example/page', $response->json('resolved_url'));
        $response->assertJson([
            'was_internal' => true,
            'redirect_count' => 1,
        ]);
    }

    public function test_internal_cycle_returns_unprocessable(): void
    {
        $user = User::factory()->create();
        ShortLink::factory()->create([
            'slug' => 'loop-a',
            'destination_url' => 'https://resolver-app.test/loop-b',
            'is_active' => true,
        ]);
        ShortLink::factory()->create([
            'slug' => 'loop-b',
            'destination_url' => 'https://resolver-app.test/loop-a',
            'is_active' => true,
        ]);

        $this->actingAs($user)->postJson(route('links.expand.submit'), [
            'url' => 'https://resolver-app.test/loop-a',
        ])->assertStatus(422);
    }

    public function test_internal_unknown_slug_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('links.expand.submit'), [
            'url' => 'https://resolver-app.test/missing-slug-xyz',
        ])->assertNotFound();
    }

    public function test_internal_expired_slug_returns_gone(): void
    {
        $user = User::factory()->create();
        ShortLink::factory()->create([
            'slug' => 'old',
            'expires_at' => now()->subMinute(),
            'is_active' => true,
        ]);

        $this->actingAs($user)->postJson(route('links.expand.submit'), [
            'url' => 'https://resolver-app.test/old',
        ])->assertStatus(410);
    }

    public function test_internal_reserved_path_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('links.expand.submit'), [
            'url' => 'https://resolver-app.test/dashboard',
        ])->assertNotFound();
    }

    public function test_external_url_follows_redirects(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();
            $method = $request->method();

            if ($url === 'https://chain.test/start' && $method === 'HEAD') {
                return Http::response('', 302, ['Location' => 'https://chain.test/next']);
            }
            if ($url === 'https://chain.test/next' && $method === 'HEAD') {
                return Http::response('', 200);
            }

            return Http::response('unexpected', 500);
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('links.expand.submit'), [
            'url' => 'https://chain.test/start',
        ]);

        $response->assertOk();
        $response->assertJson([
            'resolved_url' => 'https://chain.test/next',
            'was_internal' => false,
            'redirect_count' => 1,
        ]);
    }

    public function test_private_ip_literal_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('links.expand.submit'), [
            'url' => 'http://192.168.0.10/path',
        ])->assertStatus(422);
    }

    public function test_validation_rejects_invalid_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('links.expand.submit'), [
            'url' => 'not-a-url',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }
}
