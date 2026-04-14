<?php

namespace App\Support;

use Illuminate\Http\Request;

final class BotTrafficRateLimiterKeys
{
    public static function redirectLikelyBot(Request $request, string $slug): string
    {
        $ip = $request->ip() ?? '';

        return 'bot-traffic:redirect:'.hash_hmac('sha256', $ip.':'.mb_strtolower($slug), (string) config('app.key'));
    }

    public static function resolverLikelyBot(Request $request, int $userId): string
    {
        $ip = $request->ip() ?? '';

        return 'bot-traffic:resolver:'.hash_hmac('sha256', (string) $userId.':'.$ip, (string) config('app.key'));
    }
}
