<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type PaginationLinkItem = {
    url: string | null;
    label: string;
    active?: boolean;
};

export type PaginationMeta = {
    from: number | null;
    to: number | null;
    total: number;
};

type Props = {
    links: PaginationLinkItem[];
    /** Inertia partial reload — only refresh these props on page change */
    only?: string[];
    preserveScroll?: boolean;
    /** When set, shows “Showing x–y of z” above the controls on larger screens */
    meta?: PaginationMeta;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    preserveScroll: true,
});

const summary = computed((): string | null => {
    const m = props.meta;
    if (!m || m.total === 0) {
        return null;
    }
    if (m.from === null || m.to === null) {
        return `${m.total} ${m.total === 1 ? 'result' : 'results'}`;
    }

    return `Showing ${m.from}–${m.to} of ${m.total}`;
});
</script>

<template>
    <div
        v-if="links.length > 0"
        :class="
            cn(
                'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4',
                props.class,
            )
        "
    >
        <p
            v-if="summary"
            class="text-sm text-muted-foreground tabular-nums"
            aria-live="polite"
        >
            {{ summary }}
        </p>

        <nav class="flex flex-wrap items-center gap-1" aria-label="Pagination">
            <template
                v-for="(item, index) in links"
                :key="`${index}-${item.label}-${item.url ?? ''}`"
            >
                <Button
                    v-if="item.url"
                    variant="outline"
                    size="sm"
                    :class="
                        item.active
                            ? 'border-primary bg-primary/10 font-medium text-primary shadow-none'
                            : ''
                    "
                    as-child
                >
                    <Link
                        :href="item.url"
                        :preserve-scroll="preserveScroll"
                        :only="only"
                    >
                        <span v-html="item.label" />
                    </Link>
                </Button>
                <Button
                    v-else
                    variant="outline"
                    size="sm"
                    disabled
                    :class="
                        item.active
                            ? 'pointer-events-none border-primary bg-primary/10 font-medium text-primary opacity-100 shadow-none'
                            : ''
                    "
                >
                    <span v-html="item.label" />
                </Button>
            </template>
        </nav>
    </div>
</template>
