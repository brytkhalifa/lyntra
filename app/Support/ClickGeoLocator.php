<?php

namespace App\Support;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Config;
use MaxMind\Db\InvalidDatabaseException;
use Throwable;

final class ClickGeoLocator
{
    private static ?Reader $reader = null;

    /**
     * Release the cached Reader (for tests).
     */
    public static function forgetReader(): void
    {
        if (self::$reader !== null) {
            self::$reader->close();
            self::$reader = null;
        }
    }

    /**
     * @return array{country_code: ?string, region: ?string, city: ?string}
     */
    public static function fromClientIp(?string $ip): array
    {
        $empty = ['country_code' => null, 'region' => null, 'city' => null];

        if (! (bool) Config::get('geoip.enabled', false)) {
            return $empty;
        }

        if ($ip === null || $ip === '') {
            return $empty;
        }

        if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'], true)) {
            return $empty;
        }

        $path = Config::get('geoip.database');
        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return $empty;
        }

        try {
            $reader = self::reader($path);
            $record = $reader->city($ip);

            $countryCode = $record->country->isoCode ?? null;
            if (is_string($countryCode)) {
                $countryCode = mb_strtoupper(mb_substr($countryCode, 0, 2));
            } else {
                $countryCode = null;
            }

            $region = null;
            if ($record->mostSpecificSubdivision->isoCode !== null && $record->mostSpecificSubdivision->isoCode !== '') {
                $region = self::truncate($record->mostSpecificSubdivision->isoCode, 255);
            } elseif ($record->mostSpecificSubdivision->name !== null && $record->mostSpecificSubdivision->name !== '') {
                $region = self::truncate($record->mostSpecificSubdivision->name, 255);
            }

            $city = null;
            if ($record->city->name !== null && $record->city->name !== '') {
                $city = self::truncate($record->city->name, 255);
            }

            return [
                'country_code' => $countryCode,
                'region' => $region,
                'city' => $city,
            ];
        } catch (AddressNotFoundException) {
            return $empty;
        } catch (InvalidDatabaseException) {
            return $empty;
        } catch (Throwable) {
            return $empty;
        }
    }

    private static function reader(string $path): Reader
    {
        if (self::$reader === null) {
            self::$reader = new Reader($path);
        }

        return self::$reader;
    }

    private static function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max);
    }
}
