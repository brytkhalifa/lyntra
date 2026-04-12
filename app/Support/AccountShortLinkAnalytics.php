<?php

namespace App\Support;

use App\Models\LinkClick;
use App\Models\ShortLink;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class AccountShortLinkAnalytics
{
    /**
     * @return array{
     *     total_links: int,
     *     total_clicks: int,
     *     clicks_last_30_days: int,
     *     active_links: int,
     * }
     */
    public static function summary(User $user): array
    {
        $userId = $user->id;

        $totalLinks = ShortLink::query()->where('user_id', $userId)->count();

        $totalClicks = LinkClick::query()
            ->join('short_links', 'link_clicks.short_link_id', '=', 'short_links.id')
            ->where('short_links.user_id', $userId)
            ->count();

        $clicksWindowStart = CarbonImmutable::now()->subDays(29)->startOfDay();

        $clicksLast30Days = LinkClick::query()
            ->join('short_links', 'link_clicks.short_link_id', '=', 'short_links.id')
            ->where('short_links.user_id', $userId)
            ->where('link_clicks.created_at', '>=', $clicksWindowStart)
            ->count();

        $activeLinks = ShortLink::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        return [
            'total_links' => $totalLinks,
            'total_clicks' => $totalClicks,
            'clicks_last_30_days' => $clicksLast30Days,
            'active_links' => $activeLinks,
        ];
    }

    /**
     * Daily click counts for the last N calendar days (inclusive of today), scoped to the user's links.
     *
     * @return list<array{date: string, count: int}>
     */
    public static function clicksByDay(User $user, int $days = 30): array
    {
        $days = max(1, $days);
        $driver = DB::connection()->getDriverName();
        $dayExpr = match ($driver) {
            'pgsql' => 'link_clicks.created_at::date',
            'sqlite' => 'date(link_clicks.created_at)',
            'mysql' => 'DATE(link_clicks.created_at)',
            default => 'DATE(link_clicks.created_at)',
        };

        $start = CarbonImmutable::now()->subDays($days - 1)->startOfDay();
        $dayRaw = DB::raw($dayExpr);

        $rows = LinkClick::query()
            ->join('short_links', 'link_clicks.short_link_id', '=', 'short_links.id')
            ->where('short_links.user_id', $user->id)
            ->where('link_clicks.created_at', '>=', $start)
            ->selectRaw("{$dayExpr} as day, COUNT(*) as c")
            ->groupBy($dayRaw)
            ->orderBy('day')
            ->get();

        $countsByDay = [];
        foreach ($rows as $row) {
            $day = $row->day instanceof \DateTimeInterface
                ? CarbonImmutable::instance($row->day)->toDateString()
                : (string) $row->day;
            $countsByDay[$day] = (int) $row->c;
        }

        $series = [];
        $cursor = $start;
        $end = CarbonImmutable::now()->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $series[] = [
                'date' => $key,
                'count' => $countsByDay[$key] ?? 0,
            ];
            $cursor = $cursor->addDay();
        }

        return $series;
    }

    /**
     * @return list<array{
     *     id: int,
     *     slug: string,
     *     short_url: string,
     *     destination_url: string,
     *     clicks_count: int,
     *     created_at: string,
     * }>
     */
    public static function topLinksByClicks(User $user, int $limit = 5): array
    {
        $limit = max(1, $limit);

        return ShortLink::query()
            ->where('user_id', $user->id)
            ->withCount('linkClicks')
            ->orderByDesc('link_clicks_count')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (ShortLink $link): array => self::linkRow($link))
            ->values()
            ->all();
    }

    /**
     * @return list<array{
     *     id: int,
     *     slug: string,
     *     short_url: string,
     *     destination_url: string,
     *     clicks_count: int,
     *     created_at: string,
     * }>
     */
    public static function recentLinks(User $user, int $limit = 5): array
    {
        $limit = max(1, $limit);

        return ShortLink::query()
            ->where('user_id', $user->id)
            ->withCount('linkClicks')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ShortLink $link): array => self::linkRow($link))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     id: int,
     *     slug: string,
     *     short_url: string,
     *     destination_url: string,
     *     clicks_count: int,
     *     created_at: string,
     * }
     */
    private static function linkRow(ShortLink $link): array
    {
        return [
            'id' => $link->id,
            'slug' => $link->slug,
            'short_url' => url('/'.$link->slug),
            'destination_url' => $link->destination_url,
            'clicks_count' => $link->link_clicks_count,
            'created_at' => $link->created_at->toIso8601String(),
        ];
    }
}
