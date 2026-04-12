<script setup lang="ts">
import { ChevronDown, Download, Eye, QrCode } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { qr as linksQr } from '@/routes/links';

const props = withDefaults(
    defineProps<{
        linkId: number;
        /** `icon` for table rows; `button` for toolbar-style actions */
        trigger?: 'icon' | 'button';
    }>(),
    { trigger: 'icon' },
);

const viewUrl = computed(() =>
    linksQr.url(props.linkId, {
        query: { format: 'svg', inline: '1' },
    }),
);

const downloadUrl = computed(() => linksQr.url(props.linkId));
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                :variant="trigger === 'button' ? 'outline' : 'ghost'"
                :size="trigger === 'button' ? 'sm' : 'icon'"
                type="button"
            >
                <QrCode
                    :class="
                        trigger === 'button' ? 'mr-1 size-4' : 'size-4'
                    "
                />
                <template v-if="trigger === 'button'">
                    QR code
                    <ChevronDown class="ml-1 size-4 opacity-60" />
                </template>
                <span v-else class="sr-only">QR code options</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem as-child>
                <a
                    :href="viewUrl"
                    class="flex cursor-pointer items-center"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <Eye class="mr-2 size-4" />
                    View
                </a>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <a
                    :href="downloadUrl"
                    class="flex cursor-pointer items-center"
                    download
                >
                    <Download class="mr-2 size-4" />
                    Download
                </a>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
