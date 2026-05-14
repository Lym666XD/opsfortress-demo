import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

export default function Dashboard() {
    return (
        <>
            <Head title="OpsFortress Demo" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="rounded-2xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm dark:border-sidebar-border dark:bg-black/10">
                    <p className="text-sm font-medium uppercase tracking-[0.24em] text-muted-foreground">OpsFortress Demo</p>
                    <h1 className="mt-3 text-3xl font-semibold tracking-tight">WHS platform scaffold</h1>
                    <p className="mt-3 max-w-3xl text-sm leading-6 text-muted-foreground">
                        This starter now targets a multi-tenant WHS platform with PostgreSQL, Redis queues, S3-compatible
                        storage, Inertia React, and reusable task-pack workflows for worker and admin flows.
                    </p>
                </section>

                <div className="grid gap-4 md:grid-cols-3">
                    <article className="rounded-2xl border border-sidebar-border/70 bg-white/80 p-5 shadow-sm dark:border-sidebar-border dark:bg-black/10">
                        <p className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">Core Platform</p>
                        <h2 className="mt-2 text-lg font-semibold">Tenants, businesses, workplaces</h2>
                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                            The first migration pass establishes tenancy boundaries, business identity, worker records,
                            roles, occupations, and workplace assignment.
                        </p>
                    </article>

                    <article className="rounded-2xl border border-sidebar-border/70 bg-white/80 p-5 shadow-sm dark:border-sidebar-border dark:bg-black/10">
                        <p className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">Workflow Layer</p>
                        <h2 className="mt-2 text-lg font-semibold">Task packs and submissions</h2>
                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                            The demo foundation includes reusable task packs, occupation matching, activity tracking,
                            submissions, and generated document records.
                        </p>
                    </article>

                    <article className="rounded-2xl border border-sidebar-border/70 bg-white/80 p-5 shadow-sm dark:border-sidebar-border dark:bg-black/10">
                        <p className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">Infrastructure</p>
                        <h2 className="mt-2 text-lg font-semibold">Redis, S3, and PWA-ready</h2>
                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                            Environment defaults now point at PostgreSQL, Redis queues, and S3-compatible storage so the
                            app is aligned with the intended production shape from the outset.
                        </p>
                    </article>
                </div>

                <section className="rounded-2xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm dark:border-sidebar-border dark:bg-black/10">
                    <p className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">Next Build Slice</p>
                    <div className="mt-3 grid gap-3 md:grid-cols-2">
                        <div className="rounded-xl bg-muted/60 p-4">
                            <h3 className="font-medium">Admin workflow</h3>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Business onboarding, workplace setup, worker assignment, and task-pack eligibility rules.
                            </p>
                        </div>
                        <div className="rounded-xl bg-muted/60 p-4">
                            <h3 className="font-medium">Worker workflow</h3>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Workplace sign-in, SWMS acknowledgement, pre-start completion, and queued PDF generation.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
