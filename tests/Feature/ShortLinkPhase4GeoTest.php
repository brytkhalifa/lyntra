<?php

namespace Tests\Feature;

use App\Models\LinkClick;
use App\Models\ShortLink;
use App\Support\ClickGeoLocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortLinkPhase4GeoTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ClickGeoLocator::forgetReader();

        parent::tearDown();
    }

    public function test_geo_fields_null_when_geoip_disabled(): void
    {
        config(['geoip.enabled' => false]);

        $link = ShortLink::factory()->create([
            'slug' => 'nogeo',
            'destination_url' => 'https://example.com',
            'is_active' => true,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '81.2.69.142'])
            ->get('/nogeo')
            ->assertRedirect();

        $click = LinkClick::query()->first();
        $this->assertNotNull($click);
        $this->assertNull($click->country_code);
        $this->assertNull($click->region);
        $this->assertNull($click->city);
    }

    public function test_geo_fields_populated_when_geoip_enabled_with_database(): void
    {
        $fixture = base_path('tests/fixtures/GeoIP2-City-Test.mmdb');
        $this->assertFileExists($fixture);

        config([
            'geoip.enabled' => true,
            'geoip.database' => $fixture,
        ]);

        $link = ShortLink::factory()->create([
            'slug' => 'withgeo',
            'destination_url' => 'https://example.com',
            'is_active' => true,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '81.2.69.142'])
            ->get('/withgeo')
            ->assertRedirect();

        $click = LinkClick::query()->first();
        $this->assertNotNull($click);
        $this->assertSame('GB', $click->country_code);
        $this->assertSame('ENG', $click->region);
        $this->assertSame('London', $click->city);
    }
}
