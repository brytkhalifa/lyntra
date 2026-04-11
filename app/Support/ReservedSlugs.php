<?php

namespace App\Support;

final class ReservedSlugs
{
    /**
     * Lowercase first-path segments that must not be used as short link slugs.
     *
     * @var list<string>
     */
    private const SLUGS = [
        '_boost',
        'api',
        'dashboard',
        'email',
        'forgot-password',
        'links',
        'login',
        'logout',
        'register',
        'reset-password',
        'sanctum',
        'settings',
        'storage',
        'two-factor-challenge',
        'up',
        'user',
    ];

    public static function contains(string $slug): bool
    {
        return in_array(mb_strtolower($slug), self::SLUGS, true);
    }
}
