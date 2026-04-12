<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BarChart3,
    Link2,
    QrCode,
    ShieldCheck,
    Sparkles,
    UnfoldVertical,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard, login, register } from '@/routes';
import { create as linksCreate, expand as linksExpand } from '@/routes/links';

const destinationUrlPlaceholder =
    'https://example.com/blog/announcements/q2-roadmap';

const draftDestinationUrl = ref('');

function createLinkHref(): string {
    const trimmed = draftDestinationUrl.value.trim();
    if (trimmed === '') {
        return linksCreate.url();
    }

    return linksCreate.url({ query: { destination_url: trimmed } });
}

function startCreateShortLink(): void {
    router.visit(createLinkHref());
}

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Short links with clarity">
        <meta
            head-key="description"
            name="description"
            content="Create memorable short links, see click trends, export QR codes, and preview destinations before you share."
        />
    </Head>

    <div
        class="relative flex min-h-screen flex-col bg-background text-foreground"
    >
        <div
            class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,var(--color-primary)/0.08,transparent)] dark:bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,var(--color-primary)/0.14,transparent)]"
            aria-hidden="true"
        />

        <header
            class="border-b border-border/60 bg-background/80 backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm"
                    >
                        <Link2 class="size-4" aria-hidden="true" />
                    </div>
                    <span class="text-lg font-semibold tracking-tight">
                        Lyntra
                    </span>
                </div>
                <nav class="flex flex-wrap items-center justify-end gap-2">
                    <template v-if="$page.props.auth.user">
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="dashboard()">Dashboard</Link>
                        </Button>
                        <Button size="sm" as-child>
                            <Link :href="linksExpand.url()">Expand link</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="login()">Log in</Link>
                        </Button>
                        <Button
                            v-if="canRegister"
                            size="sm"
                            as-child
                            class="hidden sm:inline-flex"
                        >
                            <Link :href="register()">Create account</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex flex-1 flex-col">
            <section
                class="mx-auto flex w-full max-w-6xl flex-col gap-10 px-4 py-16 sm:px-6 sm:py-20 lg:flex-row lg:items-center lg:gap-16 lg:py-24"
            >
                <div class="flex-1 space-y-6">
                    <p
                        class="inline-flex items-center gap-2 rounded-full border border-border bg-muted/50 px-3 py-1 text-xs font-medium text-muted-foreground"
                    >
                        <Sparkles class="size-3.5" aria-hidden="true" />
                        Built for teams who care where traffic goes
                    </p>
                    <h1
                        class="text-balance text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl"
                    >
                        Short links you can trust, measure, and share.
                    </h1>
                    <p
                        class="max-w-xl text-pretty text-lg text-muted-foreground"
                    >
                        Turn long URLs into clean slugs on your domain, watch
                        clicks over time, and use expand preview when you need
                        to verify a destination before it is opened.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <template v-if="$page.props.auth.user">
                            <Button size="lg" as-child>
                                <Link :href="dashboard()">
                                    Open dashboard
                                </Link>
                            </Button>
                            <Button size="lg" variant="outline" as-child>
                                <Link :href="linksExpand.url()">
                                    <UnfoldVertical class="mr-2 size-4" />
                                    Expand a link
                                </Link>
                            </Button>
                        </template>
                        <template v-else>
                            <Button
                                v-if="canRegister"
                                size="lg"
                                as-child
                                class="w-full sm:w-auto"
                            >
                                <Link :href="register()">
                                    Get started free
                                </Link>
                            </Button>
                            <Button
                                size="lg"
                                variant="outline"
                                as-child
                                class="w-full sm:w-auto"
                            >
                                <Link :href="login()">Log in</Link>
                            </Button>
                        </template>
                    </div>
                </div>
                <div class="flex flex-1 justify-center lg:justify-end">
                    <div
                        class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-lg"
                    >
                        <form
                            class="space-y-4"
                            @submit.prevent="startCreateShortLink"
                        >
                            <div class="grid gap-2">
                                <Label
                                    for="landing_destination_url"
                                    class="text-xs font-medium uppercase tracking-wide text-muted-foreground"
                                >
                                    Paste a long URL
                                </Label>
                                <Input
                                    id="landing_destination_url"
                                    v-model="draftDestinationUrl"
                                    type="url"
                                    name="landing_destination_url"
                                    autocomplete="off"
                                    :placeholder="destinationUrlPlaceholder"
                                    class="font-mono text-sm"
                                />
                            </div>
                            <Button type="submit" class="w-full">
                                <Link2 class="mr-2 size-4" aria-hidden="true" />
                                Create short link
                            </Button>
                        </form>
                        <div class="my-4 h-px bg-border" />
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            Your short link could look like
                        </p>
                        <p class="mt-3 break-all font-mono text-base font-medium text-primary">
                            lyntra.com/qjklske
                        </p>
                        <p class="mt-4 text-sm text-muted-foreground">
                            Same destination, fewer characters, and click
                            analytics on your dashboard. Sign in if prompted,
                            then finish on the create page.
                        </p>
                    </div>
                </div>
            </section>

            <section
                class="border-t border-border/60 bg-muted/30 py-16 sm:py-20"
            >
                <div class="mx-auto max-w-6xl px-4 sm:px-6">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Everything you need around the link
                        </h2>
                        <p class="mt-3 text-muted-foreground">
                            Practical tools for creating, distributing, and
                            understanding short URLs in one place.
                        </p>
                    </div>
                    <div
                        class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6"
                    >
                        <Card class="border-border/80 shadow-xs">
                            <CardHeader class="space-y-3">
                                <div
                                    class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                >
                                    <Link2 class="size-5" aria-hidden="true" />
                                </div>
                                <CardTitle class="text-lg">
                                    Clean slugs
                                </CardTitle>
                                <CardDescription>
                                    Create branded short paths that are easy to
                                    read in emails, decks, and social posts.
                                </CardDescription>
                            </CardHeader>
                        </Card>
                        <Card class="border-border/80 shadow-xs">
                            <CardHeader class="space-y-3">
                                <div
                                    class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                >
                                    <BarChart3
                                        class="size-5"
                                        aria-hidden="true"
                                    />
                                </div>
                                <CardTitle class="text-lg">
                                    Click insight
                                </CardTitle>
                                <CardDescription>
                                    See totals and daily trends so you know
                                    which campaigns and assets perform best.
                                </CardDescription>
                            </CardHeader>
                        </Card>
                        <Card class="border-border/80 shadow-xs">
                            <CardHeader class="space-y-3">
                                <div
                                    class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                >
                                    <QrCode
                                        class="size-5"
                                        aria-hidden="true"
                                    />
                                </div>
                                <CardTitle class="text-lg">
                                    QR codes
                                </CardTitle>
                                <CardDescription>
                                    Generate QR images for print and events
                                    without leaving the link detail page.
                                </CardDescription>
                            </CardHeader>
                        </Card>
                        <Card class="border-border/80 shadow-xs">
                            <CardHeader class="space-y-3">
                                <div
                                    class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                >
                                    <UnfoldVertical
                                        class="size-5"
                                        aria-hidden="true"
                                    />
                                </div>
                                <CardTitle class="text-lg">
                                    Expand preview
                                </CardTitle>
                                <CardDescription>
                                    Resolve a short URL to its final destination
                                    before you click, after you sign in.
                                </CardDescription>
                            </CardHeader>
                        </Card>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
                <div
                    class="flex flex-col items-start gap-6 rounded-2xl border border-border bg-card p-8 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-10"
                >
                    <div class="flex gap-4">
                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-muted text-foreground"
                        >
                            <ShieldCheck class="size-6" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight">
                                Accounts with verification
                            </h2>
                            <p class="mt-2 max-w-xl text-muted-foreground">
                                Sensitive actions stay behind authentication and
                                email verification so your links and analytics
                                stay yours.
                            </p>
                        </div>
                    </div>
                    <Button v-if="!$page.props.auth.user" size="lg" as-child>
                        <Link :href="canRegister ? register() : login()">
                            {{ canRegister ? 'Create your account' : 'Log in' }}
                        </Link>
                    </Button>
                    <Button v-else size="lg" as-child>
                        <Link :href="dashboard()">Go to dashboard</Link>
                    </Button>
                </div>
            </section>
        </main>

        <footer
            class="border-t border-border/60 py-8 text-center text-sm text-muted-foreground"
        >
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <p>&copy; {{ new Date().getFullYear() }} Lyntra. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
