<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { index as linksIndex, update } from '@/routes/links';

type ShortLinkPayload = {
    id: number;
    slug: string;
    short_url: string;
    destination_url: string;
    utm_source: string | null;
    utm_medium: string | null;
    utm_campaign: string | null;
    utm_term: string | null;
    utm_content: string | null;
    expires_at: string | null;
    is_active: boolean;
};

defineProps<{
    shortLink: ShortLinkPayload;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Links', href: linksIndex() },
            {
                title: 'Edit',
                href: '#',
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${shortLink.slug}`" />

    <h1 class="sr-only">Edit short link</h1>

    <div class="mx-auto flex max-w-xl flex-col gap-6 p-4">
        <Heading
            variant="small"
            title="Edit link"
            :description="shortLink.short_url"
        />

        <Form
            v-bind="update.form.patch(shortLink.id)"
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
                    :default-value="shortLink.destination_url"
                />
                <InputError :message="errors.destination_url" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    name="slug"
                    required
                    autocomplete="off"
                    :default-value="shortLink.slug"
                />
                <InputError :message="errors.slug" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="utm_source">UTM source</Label>
                    <Input
                        id="utm_source"
                        name="utm_source"
                        autocomplete="off"
                        :default-value="shortLink.utm_source ?? ''"
                    />
                    <InputError :message="errors.utm_source" />
                </div>
                <div class="grid gap-2">
                    <Label for="utm_medium">UTM medium</Label>
                    <Input
                        id="utm_medium"
                        name="utm_medium"
                        autocomplete="off"
                        :default-value="shortLink.utm_medium ?? ''"
                    />
                    <InputError :message="errors.utm_medium" />
                </div>
                <div class="grid gap-2">
                    <Label for="utm_campaign">UTM campaign</Label>
                    <Input
                        id="utm_campaign"
                        name="utm_campaign"
                        autocomplete="off"
                        :default-value="shortLink.utm_campaign ?? ''"
                    />
                    <InputError :message="errors.utm_campaign" />
                </div>
                <div class="grid gap-2">
                    <Label for="utm_term">UTM term</Label>
                    <Input
                        id="utm_term"
                        name="utm_term"
                        autocomplete="off"
                        :default-value="shortLink.utm_term ?? ''"
                    />
                    <InputError :message="errors.utm_term" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="utm_content">UTM content</Label>
                <Input
                    id="utm_content"
                    name="utm_content"
                    autocomplete="off"
                    :default-value="shortLink.utm_content ?? ''"
                />
                <InputError :message="errors.utm_content" />
            </div>

            <div class="grid gap-2">
                <Label for="expires_at">Expires (optional)</Label>
                <Input
                    id="expires_at"
                    name="expires_at"
                    type="datetime-local"
                    :default-value="shortLink.expires_at ?? ''"
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
                        class="size-4 rounded border border-input shadow-xs"
                        :checked="shortLink.is_active"
                    />
                    <Label for="is_active" class="font-normal"
                        >Link is active</Label
                    >
                </div>
                <InputError :message="errors.is_active" />
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing"
                    >Save changes</Button
                >
                <Button variant="outline" as-child>
                    <Link :href="linksIndex()">Back</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
