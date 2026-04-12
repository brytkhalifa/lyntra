<?php

namespace Tests\Feature;

use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortLinkQrCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_download_qr_code(): void
    {
        $link = ShortLink::factory()->create();

        $this->get(route('links.qr', $link))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_download_svg_qr_for_short_url(): void
    {
        $user = User::factory()->create();
        $link = ShortLink::factory()->for($user)->create(['slug' => 'qrslug']);

        $response = $this->actingAs($user)->get(route('links.qr', [
            'link' => $link,
            'format' => 'svg',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString(
            '<svg',
            (string) $response->getContent(),
        );
        $response->assertHeader(
            'Content-Disposition',
            'attachment; filename="qrslug-qr.svg"',
        );
    }

    public function test_inline_query_sets_inline_content_disposition(): void
    {
        $user = User::factory()->create();
        $link = ShortLink::factory()->for($user)->create(['slug' => 'inlineqr']);

        $response = $this->actingAs($user)->get(route('links.qr', [
            'link' => $link,
            'format' => 'svg',
            'inline' => '1',
        ]));

        $response->assertOk();
        $response->assertHeader(
            'Content-Disposition',
            'inline; filename="inlineqr-qr.svg"',
        );
    }

    public function test_owner_can_download_png_qr_when_gd_is_available(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension required for PNG QR output.');
        }

        $user = User::factory()->create();
        $link = ShortLink::factory()->for($user)->create(['slug' => 'pngqr']);

        $response = $this->actingAs($user)->get(route('links.qr', $link));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertSame("\x89PNG\r\n\x1a\n", substr((string) $response->getContent(), 0, 8));
        $response->assertHeader(
            'Content-Disposition',
            'attachment; filename="pngqr-qr.png"',
        );
    }

    public function test_non_owner_cannot_download_qr_code(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $link = ShortLink::factory()->for($owner)->create();

        $this->actingAs($intruder)
            ->get(route('links.qr', $link))
            ->assertForbidden();
    }

    public function test_invalid_format_returns_unprocessable(): void
    {
        $user = User::factory()->create();
        $link = ShortLink::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('links.qr', ['link' => $link, 'format' => 'pdf']))
            ->assertStatus(422);
    }
}
