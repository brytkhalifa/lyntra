<?php

namespace App\Support;

use Illuminate\Http\Request;

final class UserAgentInsights
{
    /**
     * @return array{device_type: string|null, browser: string|null, os: string|null}
     */
    public static function fromRequest(Request $request): array
    {
        $ua = (string) $request->userAgent();

        return [
            'device_type' => self::deviceType($ua),
            'browser' => self::browser($ua),
            'os' => self::operatingSystem($ua),
        ];
    }

    private static function deviceType(string $ua): ?string
    {
        if ($ua === '') {
            return null;
        }

        $lower = mb_strtolower($ua);
        if (str_contains($lower, 'ipad') || (str_contains($lower, 'tablet') && ! str_contains($lower, 'mobile'))) {
            return 'tablet';
        }
        if (str_contains($lower, 'mobile') || str_contains($lower, 'android') || str_contains($lower, 'iphone') || str_contains($lower, 'ipod')) {
            return 'mobile';
        }

        return 'desktop';
    }

    private static function browser(string $ua): ?string
    {
        if ($ua === '') {
            return null;
        }

        $order = ['Edg', 'Chrome', 'Safari', 'Firefox', 'MSIE', 'Trident'];
        foreach ($order as $name) {
            if (str_contains($ua, $name)) {
                if ($name === 'Edg') {
                    return 'Edge';
                }
                if ($name === 'Trident' || $name === 'MSIE') {
                    return 'Internet Explorer';
                }

                return $name;
            }
        }

        return 'Other';
    }

    private static function operatingSystem(string $ua): ?string
    {
        if ($ua === '') {
            return null;
        }

        if (str_contains($ua, 'Windows')) {
            return 'Windows';
        }
        if (str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh')) {
            return 'macOS';
        }
        if (str_contains($ua, 'Linux')) {
            return 'Linux';
        }
        if (str_contains($ua, 'Android')) {
            return 'Android';
        }
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') || str_contains($ua, 'iPod')
            || str_contains($ua, 'CPU OS') || str_contains($ua, 'CPU iPhone OS')) {
            return 'iOS';
        }

        return 'Other';
    }
}
