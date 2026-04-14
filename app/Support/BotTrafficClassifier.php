<?php

namespace App\Support;

use Illuminate\Http\Request;

final class BotTrafficClassifier
{
    /**
     * Deterministic classification: true when the client is treated as likely
     * automated (known signatures or missing User-Agent).
     */
    public static function isLikelyAutomated(Request $request): bool
    {
        $ua = (string) $request->userAgent();

        if (trim($ua) === '') {
            return true;
        }

        $lower = mb_strtolower($ua);
        $signatures = config('bot-detection.ua_substrings', []);

        if (! is_array($signatures)) {
            return false;
        }

        foreach ($signatures as $fragment) {
            if (! is_string($fragment) || $fragment === '') {
                continue;
            }

            if (str_contains($lower, mb_strtolower($fragment))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Stable reason code for persistence and dashboards.
     *
     * Only meaningful when {@see self::isLikelyAutomated()} is true for the same request.
     */
    public static function automatedReasonCode(Request $request): string
    {
        $ua = (string) $request->userAgent();

        if (trim($ua) === '') {
            return 'missing_user_agent';
        }

        return 'known_bot_signature';
    }
}
