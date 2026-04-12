<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    BarChart3,
    Link2,
    Pencil,
    Plus,
    Trash2,
    UnfoldVertical,
} from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import {
    expand as linksExpand,
    index as linksIndex,
    create as linksCreate,
    edit as linksEdit,
    destroy as linksDestroy,
    show as linksShow,
} from '@/routes/links';

type Row = {
    id: number;
    slug: string;
    short_url: string;
    destination_url: string;
    is_active: boolean;
    expires_at: string | null;
    created_at: string;
    clicks_count: number;
};

type Paginated = {
    data: Row[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

defineProps<{
    shortLinks: Paginated;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Links', href: linksIndex() },
        ],
    },
});
</script>

<template>
    <Head title="Links" />

    <h1 class="sr-only">Short links</h1>

    <div class="flex flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                variant="small"
                title="Short links"
                description="Create and manage your public short URLs"
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
            class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-xs dark:border-sidebar-border"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead
                        class="border-b border-sidebar-border/70 bg-muted/40 text-muted-foreground dark:border-sidebar-border"
                    >
                        <tr>
                            <th class="px-4 py-3 font-medium">Short URL</th>
                            <th class="px-4 py-3 font-medium">Destination</th>
                            <th class="px-4 py-3 font-medium">Clicks</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="w-px px-4 py-3 font-medium" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!shortLinks.data.length">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No links yet. Create your first short link.
                            </td>
                        </tr>
                        <tr
                            v-for="row in shortLinks.data"
                            :key="row.id"
                            class="border-b border-sidebar-border/50 last:border-0 dark:border-sidebar-border/80"
                        >
                            <td class="px-4 py-3">
                                <div
                                    class="flex items-center gap-2 font-medium"
                                >
                                    <Link2
                                        class="size-4 shrink-0 text-muted-foreground"
                                    />
                                    <a
                                        :href="row.short_url"
                                        class="text-primary underline-offset-4 hover:underline"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{
                                            row.short_url.replace(
                                                /^https?:\/\//,
                                                '',
                                            )
                                        }}
                                    </a>
                                </div>
                            </td>
                            <td
                                class="max-w-[220px] truncate px-4 py-3 text-muted-foreground"
                                :title="row.destination_url"
                            >
                                {{ row.destination_url }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ row.clicks_count }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="row.is_active"
                                    class="rounded-full bg-green-500/15 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400"
                                >
                                    Active
                                </span>
                                <span
                                    v-else
                                    class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                                >
                                    Inactive
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        as-child
                                    >
                                        <Link :href="linksShow(row.id)">
                                            <span class="sr-only"
                                                >Analytics</span
                                            >
                                            <BarChart3 class="size-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        as-child
                                    >
                                        <Link :href="linksEdit(row.id)">
                                            <span class="sr-only">Edit</span>
                                            <Pencil class="size-4" />
                                        </Link>
                                    </Button>
                                    <Form
                                        v-bind="linksDestroy.form(row.id)"
                                        #default="{ processing }"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            type="submit"
                                            :disabled="processing"
                                            class="text-destructive hover:text-destructive"
                                        >
                                            <span class="sr-only">Delete</span>
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </Form>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination
                v-if="shortLinks.last_page > 1"
                :links="shortLinks.links"
                :only="['shortLinks']"
                :meta="{
                    from: shortLinks.from,
                    to: shortLinks.to,
                    total: shortLinks.total,
                }"
                class="border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
            />
        </div>
    </div>
</template>
