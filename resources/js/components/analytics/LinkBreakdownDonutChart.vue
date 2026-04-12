<script setup lang="ts">
import type { Chart, ChartConfiguration } from 'chart.js';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

export type CountRow = { label: string; count: number };

const props = withDefaults(
    defineProps<{
        rows: CountRow[];
        /** Max segments before grouping remainder into "Other" */
        maxSegments?: number;
    }>(),
    { maxSegments: 8 },
);

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chart: Chart<'doughnut'> | null = null;

const chartPalette = [
    '--chart-1',
    '--chart-2',
    '--chart-3',
    '--chart-4',
    '--chart-5',
] as const;

function cssVar(name: string, fallback: string): string {
    if (typeof document === 'undefined') {
        return fallback;
    }

    const v = getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim();

    return v || fallback;
}

const segments = computed(() => {
    const rows = props.rows.filter((r) => r.count > 0);

    if (rows.length === 0) {
        return { labels: [] as string[], data: [] as number[], colors: [] as string[] };
    }

    const max = props.maxSegments;

    if (rows.length <= max) {
        return {
            labels: rows.map((r) => r.label),
            data: rows.map((r) => r.count),
            colors: rows.map(
                (_, i) => cssVar(chartPalette[i % chartPalette.length], 'hsl(220 70% 50%)'),
            ),
        };
    }

    const head = rows.slice(0, max - 1);
    const rest = rows.slice(max - 1);
    const otherCount = rest.reduce((s, r) => s + r.count, 0);

    return {
        labels: [...head.map((r) => r.label), 'Other'],
        data: [...head.map((r) => r.count), otherCount],
        colors: [
            ...head.map((_, i) =>
                cssVar(chartPalette[i % chartPalette.length], 'hsl(220 70% 50%)'),
            ),
            cssVar('--muted-foreground', 'hsl(0 0% 45%)'),
        ],
    };
});

function buildConfig(
    labels: string[],
    data: number[],
    colors: string[],
): ChartConfiguration<'doughnut'> {
    return {
        type: 'doughnut',
        data: {
            labels,
            datasets: [
                {
                    data,
                    backgroundColor: colors,
                    borderWidth: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        padding: 12,
                        color: cssVar('--foreground', 'hsl(0 0% 9%)'),
                    },
                },
                tooltip: {
                    callbacks: {
                        label(ctx) {
                            const raw = ctx.raw as number;
                            const sum = (ctx.dataset.data as number[]).reduce(
                                (a, b) => a + b,
                                0,
                            );
                            const pct =
                                sum > 0 ? ((100 * raw) / sum).toFixed(1) : '0';

                            return ` ${raw} (${pct}%)`;
                        },
                    },
                },
            },
        },
    };
}

async function draw(): Promise<void> {
    if (typeof window === 'undefined' || !canvasRef.value) {
        return;
    }

    chart?.destroy();
    const { labels, data, colors } = segments.value;

    if (labels.length === 0) {
        return;
    }

    const { Chart: ChartCtor, ArcElement, DoughnutController, Legend, Tooltip } =
        await import('chart.js');

    ChartCtor.register(DoughnutController, ArcElement, Tooltip, Legend);

    chart = new ChartCtor(
        canvasRef.value,
        buildConfig(labels, data, colors),
    ) as Chart<'doughnut'>;
}

onMounted(() => {
    void draw();
});

watch(
    () => [props.rows, props.maxSegments],
    () => {
        void draw();
    },
    { deep: true },
);

onBeforeUnmount(() => {
    chart?.destroy();
    chart = null;
});
</script>

<template>
    <div class="relative mx-auto h-52 w-full max-w-xs min-w-0 sm:h-56">
        <canvas
            v-if="segments.labels.length > 0"
            ref="canvasRef"
            class="max-h-full w-full"
        />
        <p
            v-else
            class="flex h-full items-center justify-center text-sm text-muted-foreground"
        >
            No data.
        </p>
    </div>
</template>
