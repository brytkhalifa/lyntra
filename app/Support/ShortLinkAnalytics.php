<?php

namespace App\Support;

use App\Models\BotEvent;
use App\Models\LinkClick;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ShortLinkAnalytics
{
    public static function totalClicks(int $shortLinkId): int
    {
        return LinkClick::query()->where('short_link_id', $shortLinkId)->count();
    }

    public static function filteredBotEventsLast30Days(int $shortLinkId): int
    {
        $windowStart = CarbonImmutable::now()->subDays(29)->startOfDay();

        return BotEvent::query()
            ->where('short_link_id', $shortLinkId)
            ->where('created_at', '>=', $windowStart)
            ->count();
    }

    /**
     * Daily click counts for the last N calendar days (inclusive of today), SQL-aggregated.
     *
     * @return list<array{date: string, count: int}>
     */
    public static function clicksByDay(int $shortLinkId, int $days = 30): array
    {
        $days = max(1, $days);
        $driver = DB::connection()->getDriverName();
        // This will work for MySQL as the default case
        $dayExpr = match ($driver) {
            'pgsql' => 'link_clicks.created_at::date',
            'sqlite' => 'date(link_clicks.created_at)',
            'mysql' => 'DATE(link_clicks.created_at)',
            default => 'DATE(link_clicks.created_at)',
        };

        $start = CarbonImmutable::now()->subDays($days - 1)->startOfDay();

        $dayRaw = DB::raw($dayExpr);

        $rows = LinkClick::query()
            ->where('short_link_id', $shortLinkId)
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
     * @param  'device_type'|'browser'|'os'|'country_code'  $column
     * @return list<array{label: string, count: int}>
     */
    public static function breakdown(int $shortLinkId, string $column): array
    {
        if (! in_array($column, ['device_type', 'browser', 'os', 'country_code'], true)) {
            throw new \InvalidArgumentException('Invalid breakdown column.');
        }

        $qualified = (new LinkClick)->qualifyColumn($column);
        $wrapped = DB::connection()->getQueryGrammar()->wrap($qualified);

        return LinkClick::query()
            ->where('short_link_id', $shortLinkId)
            ->selectRaw("COALESCE({$wrapped}, 'Unknown') as label, COUNT(*) as c")
            ->groupBy(DB::raw("COALESCE({$wrapped}, 'Unknown')"))
            ->orderByDesc('c')
            ->limit(12)
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'count' => (int) $row->c,
            ])
            ->values()
            ->all();
    }
}
