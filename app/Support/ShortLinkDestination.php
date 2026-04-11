<?php

namespace App\Support;

use App\Models\ShortLink;
use Illuminate\Http\Request;

final class ShortLinkDestination
{
    /**
     * Build the final redirect URL: destination query string, visitor pass-through
     * (non-UTM), then link-owned UTM parameters overriding any prior utm_* keys.
     */
    public static function resolved(ShortLink $link, Request $request): string
    {
        $parts = parse_url($link->destination_url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $link->destination_url;
        }

        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        foreach ($request->query() as $key => $value) {
            if (! is_string($key) || is_array($value)) {
                continue;
            }
            if (str_starts_with(mb_strtolower($key), 'utm_')) {
                continue;
            }
            $query[$key] = $value;
        }

        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $utmKey) {
            $value = $link->{$utmKey};
            if ($value !== null && $value !== '') {
                $query[$utmKey] = $value;
            }
        }

        $path = $parts['path'] ?? '';
        $builtPath = ($path === '' || $path === '0') ? '/' : $path;

        $url = $parts['scheme'].'://'.$parts['host'];
        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }
        $url .= $builtPath;

        $qs = http_build_query($query);
        if ($qs !== '') {
            $url .= '?'.$qs;
        }
        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }
}
