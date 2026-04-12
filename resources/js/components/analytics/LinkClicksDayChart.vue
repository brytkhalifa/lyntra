<script setup lang="ts">
import type { Chart, ChartConfiguration } from 'chart.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

export type DayRow = { date: string; count: number };

const props = defineProps<{
    days: DayRow[];
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chart: Chart<'bar'> | null = null;

function cssVar(name: string, fallback: string): string {
    if (typeof document === 'undefined') {
        return fallback;
    }

    const v = getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim();

    return v || fallback;
}

function formatDayLabel(isoDate: string): string {
    const d = new Date(isoDate + 'T12:00:00');

    return d.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

function buildConfig(days: DayRow[]): ChartConfiguration<'bar'> {
    const labels = days.map((d) => formatDayLabel(d.date));
    const data = days.map((d) => d.count);

    return {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Clicks',
                    data,
                    backgroundColor: cssVar('--chart-1', 'hsl(220 70% 50%)'),
                    borderRadius: 4,
                    maxBarThickness: 14,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        title(items) {
                            const i = items[0]?.dataIndex ?? 0;
                            const row = days[i];

                            return row ? row.date : '';
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: cssVar('--muted-foreground', 'hsl(0 0% 45%)'),
                        maxRotation: 45,
                        minRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 12,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: cssVar('--muted-foreground', 'hsl(0 0% 45%)'),
                    },
                    grid: {
                        color: cssVar('--border', 'hsl(0 0% 90%)'),
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

    if (props.days.length === 0) {
        return;
    }

    const {
        Chart: ChartCtor,
        BarController,
        BarElement,
        CategoryScale,
        LinearScale,
        Tooltip,
    } = await import('chart.js');

    ChartCtor.register(
        BarController,
        BarElement,
        CategoryScale,
        LinearScale,
        Tooltip,
    );

    chart = new ChartCtor(canvasRef.value, buildConfig(props.days)) as Chart<'bar'>;
}

onMounted(() => {
    void draw();
});

watch(
    () => props.days,
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
    <div class="relative h-56 w-full min-w-0 sm:h-64">
        <canvas v-if="days.length > 0" ref="canvasRef" class="max-h-full w-full" />
        <p
            v-else
            class="flex h-full items-center justify-center text-sm text-muted-foreground"
        >
            No daily data.
        </p>
    </div>
</template>
