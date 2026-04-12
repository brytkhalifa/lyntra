<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useHttp } from '@inertiajs/vue3';
import { Link2, Loader2, UnfoldVertical } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { expand as expandPage, index as linksIndex } from '@/routes/links';
import expandRoutes from '@/routes/links/expand';

const http = useHttp(() => ({
    url: '',
}));

const resolvedUrl = ref<string | null>(null);
const wasInternal = ref<boolean | null>(null);
const redirectCount = ref<number | null>(null);
const generalError = ref<string | null>(null);

const expandSubmit = expandRoutes.submit;

const urlFieldError = computed((): string | undefined => {
    const e = http.errors.url;

    if (Array.isArray(e)) {
        return e[0];
    }

    return typeof e === 'string' ? e : undefined;
});

const submit = async (): Promise<void> => {
    generalError.value = null;
    resolvedUrl.value = null;
    wasInternal.value = null;
    redirectCount.value = null;
    http.clearErrors();

    try {
        const data = (await http.submit(expandSubmit.post())) as {
            resolved_url: string;
            was_internal: boolean;
            redirect_count: number;
        };

        resolvedUrl.value = data.resolved_url;
        wasInternal.value = data.was_internal;
        redirectCount.value = data.redirect_count;
    } catch (e: unknown) {
        if (urlFieldError.value) {
            return;
        }

        if (
            typeof e === 'object' &&
            e !== null &&
            'message' in e &&
            typeof (e as { message: unknown }).message === 'string'
        ) {
            generalError.value = (e as { message: string }).message;

            return;
        }

        generalError.value =
            'Could not resolve this URL. Try again or check the address.';
    }
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Links', href: linksIndex() },
            { title: 'Expand', href: expandPage.url() },
        ],
    },
});
</script>

<template>
    <Head title="Expand link" />

    <h1 class="sr-only">Expand short link</h1>

    <div class="mx-auto flex max-w-xl flex-col gap-6 p-4">
        <Heading
            variant="small"
            title="Expand a short link"
            description="Resolve Lyntra short URLs or common third-party short links to their final destination. Clicks are not recorded."
        />

        <form class="space-y-4" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="url">Short URL</Label>
                <Input
                    id="url"
                    v-model="http.url"
                    type="url"
                    name="url"
                    required
                    autocomplete="off"
                    placeholder="https://bit.ly/example or your short link"
                    :disabled="http.processing"
                />
                <InputError :message="urlFieldError" />
            </div>

            <Button
                type="submit"
                :disabled="http.processing || !http.url.trim()"
            >
                <Loader2
                    v-if="http.processing"
                    class="mr-2 size-4 animate-spin"
                />
                <UnfoldVertical v-else class="mr-2 size-4" />
                Expand URL
            </Button>

            <p v-if="generalError" class="text-sm text-destructive">
                {{ generalError }}
            </p>
        </form>

        <div
            v-if="resolvedUrl"
            class="rounded-xl border border-sidebar-border/70 bg-muted/30 p-4 dark:border-sidebar-border"
        >
            <p class="text-xs font-medium text-muted-foreground">Destination</p>
            <a
                :href="resolvedUrl"
                class="mt-1 block text-sm break-all text-primary underline-offset-4 hover:underline"
                target="_blank"
                rel="noopener noreferrer"
            >
                {{ resolvedUrl }}
            </a>
            <dl
                class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground"
            >
                <div>
                    <dt class="inline font-medium">Source:</dt>
                    <dd class="inline">
                        {{ wasInternal ? 'Lyntra' : 'External' }}
                    </dd>
                </div>
                <div v-if="redirectCount !== null">
                    <dt class="inline font-medium">Redirects:</dt>
                    <dd class="inline">{{ redirectCount }}</dd>
                </div>
            </dl>
        </div>

        <Button variant="outline" as-child>
            <Link :href="linksIndex()">
                <Link2 class="mr-2 size-4" />
                Back to links
            </Link>
        </Button>
    </div>
</template>
