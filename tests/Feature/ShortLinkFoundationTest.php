<?php

namespace Tests\Feature;

use App\Models\LinkClick;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortLinkFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_short_links_relationship(): void
    {
        $user = User::factory()->create();
        $link = ShortLink::factory()->for($user)->create();

        $this->assertCount(1, $user->fresh()->shortLinks);
        $this->assertTrue($user->shortLinks->first()->is($link));
        $this->assertTrue($link->user->is($user));
    }

    public function test_short_link_factory_creates_unique_slugs(): void
    {
        $a = ShortLink::factory()->create();
        $b = ShortLink::factory()->create();

        $this->assertNotSame($a->slug, $b->slug);
    }

    public function test_link_click_belongs_to_short_link(): void
    {
        $click = LinkClick::factory()->create();

        $this->assertInstanceOf(ShortLink::class, $click->shortLink);
        $this->assertTrue($click->shortLink->linkClicks->contains($click));
    }

    public function test_short_link_is_expired_when_expires_at_in_past(): void
    {
        $link = ShortLink::factory()->create([
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($link->isExpired());
    }

    public function test_short_link_is_not_expired_when_expires_at_null_or_future(): void
    {
        $open = ShortLink::factory()->create(['expires_at' => null]);
        $future = ShortLink::factory()->create(['expires_at' => now()->addDay()]);

        $this->assertFalse($open->isExpired());
        $this->assertFalse($future->isExpired());
    }
}
