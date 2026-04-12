<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import {
    index as linksIndex,
    create as linksCreate,
    store,
} from '@/routes/links';

const props = withDefaults(
    defineProps<{
        prefill_destination_url?: string | null;
    }>(),
    {
        prefill_destination_url: null,
    },
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Links', href: linksIndex() },
            { title: 'Create', href: linksCreate() },
        ],
    },
});
</script>

<template>
    <Head title="Create link" />

    <h1 class="sr-only">Create short link</h1>

    <div class="mx-auto flex max-w-xl flex-col gap-6 p-4">
        <Heading
            variant="small"
            title="New short link"
            description="Destination is required. Leave slug empty for a random short code."
        />

        <Form
            v-bind="store.form()"
            class="space-y-6"
            #default="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="destination_url">Destination URL</Label>
                <Input
                    id="destination_url"
                    name="destination_url"
                    type="url"
                    required
                    autocomplete="off"
                    placeholder="https://example.com/page"
                    :default-value="props.prefill_destination_url ?? undefined"
                />
                <InputError :message="errors.destination_url" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">Custom slug (optional)</Label>
                <Input
                    id="slug"
                    name="slug"
                    autocomplete="off"
                    placeholder="my-sale"
                />
                <p class="text-xs text-muted-foreground">
                    Lowercase letters, numbers, and hyphens only.
                </p>
                <InputError :message="errors.slug" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="utm_source">UTM source</Label>
                    <Input
                        id="utm_source"
                        name="utm_source"
                        autocomplete="off"
                    />
                    <InputError :message="errors.utm_source" />
                </div>
                <div class="grid gap-2">
                    <Label for="utm_medium">UTM medium</Label>
                    <Input
                        id="utm_medium"
                        name="utm_medium"
                        autocomplete="off"
                    />
                    <InputError :message="errors.utm_medium" />
                </div>
                <div class="grid gap-2">
                    <Label for="utm_campaign">UTM campaign</Label>
                    <Input
                        id="utm_campaign"
                        name="utm_campaign"
                        autocomplete="off"
                    />
                    <InputError :message="errors.utm_campaign" />
                </div>
                <div class="grid gap-2">
                    <Label for="utm_term">UTM term</Label>
                    <Input id="utm_term" name="utm_term" autocomplete="off" />
                    <InputError :message="errors.utm_term" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="utm_content">UTM content</Label>
                <Input id="utm_content" name="utm_content" autocomplete="off" />
                <InputError :message="errors.utm_content" />
            </div>

            <div class="grid gap-2">
                <Label for="expires_at">Expires (optional)</Label>
                <Input
                    id="expires_at"
                    name="expires_at"
                    type="datetime-local"
                />
                <InputError :message="errors.expires_at" />
            </div>

            <div class="flex flex-col gap-2">
                <input type="hidden" name="is_active" value="0" />
                <div class="flex items-center gap-2">
                    <input
                        id="is_active"
                        type="checkbox"
                        name="is_active"
                        value="1"
                        checked
                        class="size-4 rounded border border-input shadow-xs"
                    />
                    <Label for="is_active" class="font-normal"
                        >Link is active</Label
                    >
                </div>
                <InputError :message="errors.is_active" />
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing"
                    >Create link</Button
                >
                <Button variant="outline" as-child>
                    <Link :href="linksIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
