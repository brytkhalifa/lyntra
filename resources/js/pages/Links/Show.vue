<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, BarChart3 } from 'lucide-vue-next';
import { computed } from 'vue';
import LinkBreakdownDonutChart from '@/components/analytics/LinkBreakdownDonutChart.vue';
import LinkClicksDayChart from '@/components/analytics/LinkClicksDayChart.vue';
import Heading from '@/components/Heading.vue';
import ShortLinkQrMenu from '@/components/links/ShortLinkQrMenu.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as linksIndex } from '@/routes/links';

type ShortLinkPayload = {
    id: number;
    slug: string;
    short_url: string;
    destination_url: string;
    is_active: boolean;
    expires_at: string | null;
    created_at: string;
};

type CountRow = { label: string; count: number };
type DayRow = { date: string; count: number };

type Analytics = {
    total_clicks: number;
    filtered_bot_events_30d: number;
    clicks_by_day: DayRow[];
    by_device: CountRow[];
    by_browser: CountRow[];
    by_os: CountRow[];
    by_country: CountRow[];
};

const props = defineProps<{
    shortLink: ShortLinkPayload;
    analytics: Analytics;
}>();

const maxDayCount = computed(() =>
    props.analytics.clicks_by_day.reduce((m, d) => Math.max(m, d.count), 0),
);

function pct(part: number, whole: number): string {
    if (whole === 0) {
        return '0';
    }

    return ((100 * part) / whole).toFixed(1);
}

function formatDayLabel(isoDate: string): string {
    const d = new Date(isoDate + 'T12:00:00');

    return d.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

const hasGeo = computed(() =>
    props.analytics.by_country.some((r) => r.label !== 'Unknown'),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Links', href: linksIndex() },
            { title: 'Analytics', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Analytics · ${shortLink.slug}`" />

    <h1 class="sr-only">Link analytics</h1>

    <div class="flex flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="space-y-1">
                <Button variant="ghost" size="sm" class="-ml-2 w-fit" as-child>
                    <Link :href="linksIndex()">
                        <ArrowLeft class="mr-1 size-4" />
                        All links
                    </Link>
                </Button>
                <Heading
                    variant="small"
                    title="Link analytics"
                    :description="
                        shortLink.short_url.replace(/^https?:\/\//, '')
                    "
                />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <ShortLinkQrMenu :link-id="shortLink.id" trigger="button" />
                <Button variant="outline" size="sm" as-child>
                    <a
                        :href="shortLink.short_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Open short URL
                    </a>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle
                        class="text-sm font-medium text-muted-foreground"
                    >
                        Total clicks
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-semibold tabular-nums">
                        {{ analytics.total_clicks }}
                    </p>
                    <p class="mt-2 text-xs text-muted-foreground">
                        Filtered automated hits (30 days):
                        <span class="font-medium text-foreground tabular-nums">{{
                            analytics.filtered_bot_events_30d
                        }}</span>
                    </p>
                </CardContent>
            </Card>
            <Card class="sm:col-span-1 lg:col-span-3">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="flex items-center gap-2 text-sm font-medium"
                    >
                        <BarChart3 class="size-4 text-muted-foreground" />
                        Destination
                    </CardTitle>
                    <CardDescription class="truncate font-mono text-xs">
                        {{ shortLink.destination_url }}
                    </CardDescription>
                </CardHeader>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Clicks by day</CardTitle>
                <CardDescription
                    >Last 30 days, aggregated in the database</CardDescription
                >
            </CardHeader>
            <CardContent class="space-y-6 overflow-x-auto">
                <LinkClicksDayChart :days="analytics.clicks_by_day" />
                <table class="w-full min-w-[320px] text-left text-sm">
                    <thead
                        class="border-b border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border"
                    >
                        <tr>
                            <th class="py-2 pr-4 font-medium">Date</th>
                            <th class="py-2 pr-4 text-right font-medium">
                                Clicks
                            </th>
                            <th class="py-2 font-medium">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in analytics.clicks_by_day.filter(
                                (d) => d.count > 0,
                            )"
                            :key="row.date"
                            class="border-b border-sidebar-border/40 last:border-0 dark:border-sidebar-border/60"
                        >
                            <td
                                class="py-2 pr-4 text-muted-foreground tabular-nums"
                            >
                                {{ formatDayLabel(row.date) }}
                            </td>
                            <td class="py-2 pr-4 text-right tabular-nums">
                                {{ row.count }}
                            </td>
                            <td class="py-2">
                                <div
                                    class="h-2 max-w-[180px] overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary transition-all"
                                        :style="{
                                            width:
                                                maxDayCount > 0
                                                    ? `${(100 * row.count) / maxDayCount}%`
                                                    : '0%',
                                        }"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p
                    v-if="!analytics.clicks_by_day.some((d) => d.count > 0)"
                    class="text-sm text-muted-foreground"
                >
                    No clicks in the last 30 days yet.
                </p>
            </CardContent>
        </Card>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card
                v-for="block in [
                    { title: 'Device', rows: analytics.by_device },
                    { title: 'Browser', rows: analytics.by_browser },
                    { title: 'Operating system', rows: analytics.by_os },
                ]"
                :key="block.title"
            >
                <CardHeader>
                    <CardTitle class="text-base">{{ block.title }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <LinkBreakdownDonutChart :rows="block.rows" />
                    <table class="w-full text-left text-sm">
                        <thead
                            class="border-b border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border"
                        >
                            <tr>
                                <th class="py-2 pr-2 font-medium">
                                    {{ block.title }}
                                </th>
                                <th class="py-2 text-right font-medium">%</th>
                                <th class="w-px py-2 text-right font-medium">
                                    #
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="r in block.rows"
                                :key="`${block.title}-${r.label}`"
                                class="border-b border-sidebar-border/40 last:border-0 dark:border-sidebar-border/60"
                            >
                                <td class="max-w-[140px] truncate py-2 pr-2">
                                    {{ r.label }}
                                </td>
                                <td
                                    class="py-2 text-right text-muted-foreground tabular-nums"
                                >
                                    {{ pct(r.count, analytics.total_clicks) }}%
                                </td>
                                <td class="py-2 text-right tabular-nums">
                                    {{ r.count }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p
                        v-if="block.rows.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No data yet.
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card v-if="hasGeo">
            <CardHeader>
                <CardTitle class="text-base">Country</CardTitle>
                <CardDescription
                    >From click geo fields when present</CardDescription
                >
            </CardHeader>
            <CardContent class="space-y-4">
                <LinkBreakdownDonutChart
                    :rows="
                        analytics.by_country.filter(
                            (x) => x.label !== 'Unknown',
                        )
                    "
                />
                <table class="w-full max-w-md text-left text-sm">
                    <thead
                        class="border-b border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border"
                    >
                        <tr>
                            <th class="py-2 pr-2 font-medium">Code</th>
                            <th class="py-2 text-right font-medium">%</th>
                            <th class="w-px py-2 text-right font-medium">#</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="r in analytics.by_country.filter(
                                (x) => x.label !== 'Unknown',
                            )"
                            :key="r.label"
                            class="border-b border-sidebar-border/40 last:border-0 dark:border-sidebar-border/60"
                        >
                            <td class="py-2 pr-2">{{ r.label }}</td>
                            <td
                                class="py-2 text-right text-muted-foreground tabular-nums"
                            >
                                {{ pct(r.count, analytics.total_clicks) }}%
                            </td>
                            <td class="py-2 text-right tabular-nums">
                                {{ r.count }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
