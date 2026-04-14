<script setup lang="ts">
defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <svg
        viewBox="0 0 64 64"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
        v-bind="$attrs"
    >
        <defs>
            <linearGradient id="lyntra-bg" x1="8" y1="58" x2="56" y2="6" gradientUnits="userSpaceOnUse">
                <stop stop-color="#12092F" />
                <stop offset="0.55" stop-color="#162A5C" />
                <stop offset="1" stop-color="#34207A" />
            </linearGradient>
            <linearGradient id="lyntra-top" x1="25" y1="31" x2="53" y2="13" gradientUnits="userSpaceOnUse">
                <stop stop-color="#4F46E5" />
                <stop offset="0.45" stop-color="#3B82F6" />
                <stop offset="1" stop-color="#22D3EE" />
            </linearGradient>
            <linearGradient id="lyntra-bottom" x1="15" y1="35" x2="42" y2="53" gradientUnits="userSpaceOnUse">
                <stop stop-color="#8B5CF6" />
                <stop offset="0.45" stop-color="#4F46E5" />
                <stop offset="1" stop-color="#BFEFFF" />
            </linearGradient>
            <linearGradient id="lyntra-sheen" x1="18" y1="28" x2="39" y2="50" gradientUnits="userSpaceOnUse">
                <stop stop-color="#E0F2FE" stop-opacity="0.95" />
                <stop offset="1" stop-color="#E0F2FE" stop-opacity="0" />
            </linearGradient>
        </defs>

        <rect x="2" y="2" width="60" height="60" rx="18" fill="url(#lyntra-bg)" />

        <path
            d="M44.88 11.96c4.7-1.67 9.13-1.06 10.61 1.45 1.72 2.91-1.17 7.47-6.44 10.17l-8.62 4.42c-5.26 2.7-10.93 2.51-12.64-.4-1.48-2.51.55-6.41 4.87-9.35l.99-.66 11.23-5.63Z"
            fill="url(#lyntra-top)"
        />

        <path
            d="M24.6 23.65c2.28 1.78 6.2 2.12 10.29.33l3.32-1.46-3.67 3.1c-4.24 3.58-8.86 5.06-11.65 4.11-1.1-.37-1.96-1.09-2.44-2.08-.79-1.58-.47-3.31.85-5.02.95.08 1.99.42 3.3 1.02Z"
            fill="#160E3B"
            fill-opacity="0.9"
        />

        <path
            d="M20.07 29.77c1.84-.23 3.99.42 6.26 1.98l2.01 1.44 17.59 12.66c3.76 2.71 6.27 5.82 5.49 7.97-.95 2.64-6.61 2.71-12.14-.06L21.1 44.38c-4.8-2.47-7.63-6.28-6.3-8.52.72-1.22 2.45-1.95 5.27-2.09Z"
            fill="url(#lyntra-bottom)"
        />

        <path
            d="M27.47 34.01 44.9 46.55c2.4 1.73 3.97 3.43 4.3 4.64-.74.68-2.25 1.07-4.24 1.05-1.92-.03-4.07-.46-5.98-1.18L20.8 41.69c-2.34-1.17-3.97-2.72-4.55-4.12 1.06-.74 3.33-.86 5.91-.17 1.7.44 3.55 1.28 5.31 2.61Z"
            fill="url(#lyntra-sheen)"
            fill-opacity="0.85"
        />
    </svg>
</template>
