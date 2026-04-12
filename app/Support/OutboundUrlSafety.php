<?php

namespace App\Support;

use App\Exceptions\UnsafeUrlResolutionException;
use Illuminate\Support\Facades\Config;

final class OutboundUrlSafety
{
    /**
     * Hostnames that must never be fetched, even if DNS would be public.
     *
     * @var list<string>
     */
    private const BLOCKED_HOSTS = [
        'localhost',
        '0.0.0.0',
        '::1',
        'metadata.google.internal',
        'metadata.goog',
        '169.254.169.254',
    ];

    /**
     * @throws UnsafeUrlResolutionException
     */
    public static function assertUrlSafeToFetch(string $url): void
    {
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new UnsafeUrlResolutionException(__('Invalid URL.'));
        }

        $scheme = mb_strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new UnsafeUrlResolutionException(__('Only HTTP and HTTPS URLs can be resolved.'));
        }

        $host = mb_strtolower((string) $parts['host']);
        self::assertHostNotBlocked($host);
        self::assertHostResolvesToPublicIps($host);
    }

    /**
     * @throws UnsafeUrlResolutionException
     */
    private static function assertHostNotBlocked(string $host): void
    {
        if (in_array($host, self::BLOCKED_HOSTS, true)) {
            throw new UnsafeUrlResolutionException(__('This host cannot be resolved.'));
        }
    }

    /**
     * @throws UnsafeUrlResolutionException
     */
    private static function assertHostResolvesToPublicIps(string $host): void
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new UnsafeUrlResolutionException(__('Private or reserved addresses cannot be resolved.'));
            }

            return;
        }

        if (! (bool) Config::get('short-url-resolver.enforce_dns_public_ips', true)) {
            return;
        }

        $ips = self::resolveHostToIps($host);
        if ($ips === []) {
            throw new UnsafeUrlResolutionException(__('Host could not be resolved.'));
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new UnsafeUrlResolutionException(__('Resolved to a non-public address.'));
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function resolveHostToIps(string $host): array
    {
        $ips = [];

        $a = @dns_get_record($host, DNS_A);
        if (is_array($a)) {
            foreach ($a as $row) {
                if (isset($row['ip']) && is_string($row['ip'])) {
                    $ips[] = $row['ip'];
                }
            }
        }

        $aaaa = @dns_get_record($host, DNS_AAAA);
        if (is_array($aaaa)) {
            foreach ($aaaa as $row) {
                if (isset($row['ipv6']) && is_string($row['ipv6'])) {
                    $ips[] = $row['ipv6'];
                }
            }
        }

        if ($ips === []) {
            $legacy = @gethostbynamel($host);
            if (is_array($legacy)) {
                foreach ($legacy as $ip) {
                    if (is_string($ip) && $ip !== '') {
                        $ips[] = $ip;
                    }
                }
            } elseif (is_string($legacy) && $legacy !== '') {
                $ips[] = $legacy;
            }
        }

        return array_values(array_unique($ips));
    }
}
