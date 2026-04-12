<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BarChart3,
    Link2,
    MousePointerClick,
    Plus,
    UnfoldVertical,
} from 'lucide-vue-next';
import { computed } from 'vue';
import LinkClicksDayChart from '@/components/analytics/LinkClicksDayChart.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import {
    create as linksCreate,
    expand as linksExpand,
    index as linksIndex,
    show as linksShow,
} from '@/routes/links';

type DayRow = { date: string; count: number };

type Summary = {
    total_links: number;
    total_clicks: number;
    clicks_last_30_days: number;
    active_links: number;
};

type LinkRow = {
    id: number;
    slug: string;
    short_url: string;
    destination_url: string;
    clicks_count: number;
    created_at: string;
};

const props = defineProps<{
    summary: Summary;
    clicks_by_day: DayRow[];
    top_links: LinkRow[];
    recent_links: LinkRow[];
}>();

const hasLinks = computed(() => props.summary.total_links > 0);

function formatCreatedAt(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <h1 class="sr-only">Dashboard</h1>

    <div class="flex flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                variant="small"
                title="Dashboard"
                description="Account overview for your short links and clicks"
            />
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="linksExpand.url()">
                        <UnfoldVertical class="mr-2 size-4" />
                        Expand link
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="linksCreate()">
                        <Plus class="mr-2 size-4" />
                        New link
                    </Link>
                </Button>
            </div>
        </div>

        <div
            v-if="!hasLinks"
            class="rounded-xl border border-sidebar-border/70 bg-card p-8 text-center shadow-xs dark:border-sidebar-border"
        >
            <Link2
                class="mx-auto size-10 text-muted-foreground"
                aria-hidden="true"
            />
            <p class="mt-4 text-sm text-muted-foreground">
                You do not have any short links yet. Create one to start sharing
                URLs and tracking clicks.
            </p>
            <Button as-child class="mt-6">
                <Link :href="linksCreate()">
                    <Plus class="mr-2 size-4" />
                    Create your first link
                </Link>
            </Button>
        </div>

        <template v-else>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Short links
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold tabular-nums">
                            {{ summary.total_links }}
                        </p>
                    </CardContent>
                </Card>
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
                            {{ summary.total_clicks }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                        >
                            <MousePointerClick class="size-4 shrink-0" />
                            Clicks (30 days)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold tabular-nums">
                            {{ summary.clicks_last_30_days }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Active links
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold tabular-nums">
                            {{ summary.active_links }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Active and not expired
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <BarChart3 class="size-4 text-muted-foreground" />
                        Clicks by day
                    </CardTitle>
                    <CardDescription>
                        All your links combined, last 30 days
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <LinkClicksDayChart :days="clicks_by_day" />
                </CardContent>
            </Card>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Top links</CardTitle>
                        <CardDescription>By all-time clicks</CardDescription>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <table class="w-full min-w-[280px] text-left text-sm">
                            <thead
                                class="border-b border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border"
                            >
                                <tr>
                                    <th class="py-2 pr-3 font-medium">
                                        Short URL
                                    </th>
                                    <th class="py-2 pr-3 font-medium">
                                        Destination
                                    </th>
                                    <th class="py-2 text-right font-medium">
                                        Clicks
                                    </th>
                                    <th class="w-px py-2" />
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in top_links"
                                    :key="`top-${row.id}`"
                                    class="border-b border-sidebar-border/40 last:border-0 dark:border-sidebar-border/60"
                                >
                                    <td class="py-2 pr-3">
                                        <Link
                                            :href="linksShow(row.id)"
                                            class="font-mono text-xs text-primary underline-offset-4 hover:underline"
                                        >
                                            {{
                                                row.short_url.replace(
                                                    /^https?:\/\//,
                                                    '',
                                                )
                                            }}
                                        </Link>
                                    </td>
                                    <td
                                        class="max-w-[200px] truncate py-2 pr-3 text-muted-foreground"
                                        :title="row.destination_url"
                                    >
                                        {{ row.destination_url }}
                                    </td>
                                    <td
                                        class="py-2 text-right text-muted-foreground tabular-nums"
                                    >
                                        {{ row.clicks_count }}
                                    </td>
                                    <td class="py-2 pl-2">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            as-child
                                        >
                                            <Link :href="linksShow(row.id)">
                                                Analytics
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Recent links</CardTitle>
                        <CardDescription>Newest first</CardDescription>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <table class="w-full min-w-[280px] text-left text-sm">
                            <thead
                                class="border-b border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border"
                            >
                                <tr>
                                    <th class="py-2 pr-3 font-medium">
                                        Short URL
                                    </th>
                                    <th class="py-2 pr-3 font-medium">
                                        Created
                                    </th>
                                    <th class="py-2 text-right font-medium">
                                        Clicks
                                    </th>
                                    <th class="w-px py-2" />
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in recent_links"
                                    :key="`recent-${row.id}`"
                                    class="border-b border-sidebar-border/40 last:border-0 dark:border-sidebar-border/60"
                                >
                                    <td class="py-2 pr-3">
                                        <Link
                                            :href="linksShow(row.id)"
                                            class="font-mono text-xs text-primary underline-offset-4 hover:underline"
                                        >
                                            {{
                                                row.short_url.replace(
                                                    /^https?:\/\//,
                                                    '',
                                                )
                                            }}
                                        </Link>
                                    </td>
                                    <td
                                        class="py-2 pr-3 text-muted-foreground tabular-nums"
                                    >
                                        {{ formatCreatedAt(row.created_at) }}
                                    </td>
                                    <td
                                        class="py-2 text-right text-muted-foreground tabular-nums"
                                    >
                                        {{ row.clicks_count }}
                                    </td>
                                    <td class="py-2 pl-2">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            as-child
                                        >
                                            <Link :href="linksShow(row.id)">
                                                Analytics
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>

            <div class="flex justify-center">
                <Button variant="outline" as-child>
                    <Link :href="linksIndex()">
                        <Link2 class="mr-2 size-4" />
                        View all links
                    </Link>
                </Button>
            </div>
        </template>
    </div>
</template>
