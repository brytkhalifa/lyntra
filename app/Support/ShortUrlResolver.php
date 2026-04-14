<?php

namespace App\Support;

use App\Exceptions\UnsafeUrlResolutionException;
use App\Models\ShortLink;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils as Psr7Utils;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ShortUrlResolver
{
    /**
     * @return array{resolved_url: string, was_internal: bool, redirect_count: int}
     *
     * @throws NotFoundHttpException
     * @throws GoneHttpException
     * @throws UnsafeUrlResolutionException
     */
    public static function resolve(string $inputUrl, Request $request): array
    {
        $maxHops = max(1, (int) Config::get('short-url-resolver.max_expand_hops', 25));
        $timeout = max(1, (int) Config::get('short-url-resolver.timeout_seconds', 10));

        $current = trim($inputUrl);
        $initialParts = parse_url($current);
        $startedAsInternalShort = is_array($initialParts) && self::isInternalAppShortUrl($initialParts);

        $externalRedirectCount = 0;
        $visitedInternalSlugs = [];
        $everInternal = false;

        for ($hop = 0; $hop < $maxHops; $hop++) {
            $parts = parse_url($current);
            if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
                throw new UnsafeUrlResolutionException(__('Invalid URL.'));
            }

            $scheme = mb_strtolower((string) $parts['scheme']);
            if (! in_array($scheme, ['http', 'https'], true)) {
                throw new UnsafeUrlResolutionException(__('Only HTTP and HTTPS URLs can be resolved.'));
            }

            if (self::isInternalAppShortUrl($parts)) {
                $path = $parts['path'] ?? '/';
                $path = $path === '' ? '/' : $path;
                if (! preg_match('#^/([^/]+)/?$#', $path, $m)) {
                    $current = self::rebuildUrlFromParts($parts);

                    continue;
                }

                $slug = mb_strtolower($m[1]);
                if (ReservedSlugs::contains($slug)) {
                    throw new NotFoundHttpException(__('Short link not found.'));
                }

                if (isset($visitedInternalSlugs[$slug])) {
                    throw new UnsafeUrlResolutionException(__('Short link resolution cycle detected.'));
                }
                $visitedInternalSlugs[$slug] = true;
                $everInternal = true;

                $link = ShortLink::query()->where('slug', $slug)->first();
                if ($link === null || ! $link->is_active) {
                    throw new NotFoundHttpException(__('Short link not found.'));
                }

                if ($link->isExpired()) {
                    throw new GoneHttpException(__('This short link has expired.'));
                }

                $current = ShortLinkDestination::resolved($link, $request);

                continue;
            }

            // Internal-only expands trust the DB for the final URL (no outbound HTTP)
            // until an external redirect has been followed (recursive external + internal).
            $shouldProbeOutbound = ! $startedAsInternalShort || $externalRedirectCount > 0;
            if (! $shouldProbeOutbound) {
                return [
                    'resolved_url' => $current,
                    'was_internal' => $everInternal,
                    'redirect_count' => $externalRedirectCount,
                ];
            }

            $hopResult = self::processExternalHop($current, $timeout);
            if ($hopResult['redirect']) {
                $current = $hopResult['next'];
                $externalRedirectCount++;

                continue;
            }

            return [
                'resolved_url' => $current,
                'was_internal' => $everInternal,
                'redirect_count' => $externalRedirectCount,
            ];
        }

        throw new UnsafeUrlResolutionException(__('Too many steps while resolving this URL.'));
    }

    /**
     * @return array{redirect: bool, next?: string}
     *
     * @throws UnsafeUrlResolutionException
     */
    private static function processExternalHop(string $current, int $timeout): array
    {
        OutboundUrlSafety::assertUrlSafeToFetch($current);

        $response = self::sendHead($current, $timeout);

        if (! self::isRedirect($response) && ! $response->successful()) {
            $response = self::sendGet($current, $timeout);
        }

        if (self::isRedirect($response)) {
            $next = self::nextLocation($current, $response);
            if ($next === null || $next === '') {
                throw new UnsafeUrlResolutionException(__('Invalid redirect response.'));
            }

            return ['redirect' => true, 'next' => $next];
        }

        if ($response->successful()) {
            return ['redirect' => false];
        }

        throw new UnsafeUrlResolutionException(__('Could not resolve this URL (HTTP :status).', ['status' => $response->status()]));
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private static function isInternalAppShortUrl(array $parts): bool
    {
        $app = rtrim((string) config('app.url'), '/');
        $appParts = parse_url($app);
        if ($appParts === false || ! isset($appParts['host'])) {
            return false;
        }

        $inputHost = mb_strtolower((string) $parts['host']);
        $canonicalHost = mb_strtolower((string) $appParts['host']);
        if ($inputHost !== $canonicalHost) {
            return false;
        }

        $appPort = $appParts['port'] ?? null;
        $inputPort = $parts['port'] ?? null;
        if ((string) $appPort !== (string) $inputPort) {
            return false;
        }

        $path = $parts['path'] ?? '/';
        $path = $path === '' ? '/' : $path;

        return (bool) preg_match('#^/[^/]+/?$#', $path);
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private static function rebuildUrlFromParts(array $parts): string
    {
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $url = $scheme.'://'.$host;
        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }
        $url .= $parts['path'] ?? '';
        if (isset($parts['query'])) {
            $url .= '?'.$parts['query'];
        }
        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }

    private static function sendHead(string $url, int $timeout): Response
    {
        return Http::withoutRedirecting()
            ->timeout($timeout)
            ->connectTimeout(min(5, $timeout))
            ->withHeaders([
                'User-Agent' => 'LyntraLinkResolver/1.0',
                'Accept' => '*/*',
            ])
            ->head($url);
    }

    private static function sendGet(string $url, int $timeout): Response
    {
        return Http::withoutRedirecting()
            ->timeout($timeout)
            ->connectTimeout(min(5, $timeout))
            ->withHeaders([
                'User-Agent' => 'LyntraLinkResolver/1.0',
                'Accept' => '*/*',
            ])
            ->withOptions([
                'curl' => [
                    CURLOPT_MAXFILESIZE => 65536,
                ],
            ])
            ->get($url);
    }

    private static function isRedirect(Response $response): bool
    {
        return in_array($response->status(), [301, 302, 303, 307, 308], true);
    }

    private static function nextLocation(string $currentUrl, Response $response): ?string
    {
        $location = $response->header('Location');
        if (! is_string($location) || $location === '') {
            return null;
        }

        $base = Psr7Utils::uriFor($currentUrl);
        $target = Psr7Utils::uriFor($location);

        return (string) UriResolver::resolve($base, $target);
    }

    /**
     * When the given URL is a single-segment internal short URL for this app,
     * return the matching short link primary key; otherwise null.
     */
    public static function firstInternalShortLinkIdFromInputUrl(string $inputUrl): ?int
    {
        $current = trim($inputUrl);
        $parts = parse_url($current);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (! self::isInternalAppShortUrl($parts)) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $path = $path === '' ? '/' : $path;
        if (! preg_match('#^/([^/]+)/?$#', $path, $m)) {
            return null;
        }

        $slug = mb_strtolower($m[1]);
        if (ReservedSlugs::contains($slug)) {
            return null;
        }

        return ShortLink::query()->where('slug', $slug)->value('id');
    }
}
