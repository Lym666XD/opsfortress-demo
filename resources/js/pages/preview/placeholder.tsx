import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, ShieldCheck } from 'lucide-react';

const titles: Record<string, string> = {
    'workplace-sign-in': 'Workplace Sign-In',
    'my-task-pack': 'My Task Pack',
    'swms-sops': 'SWMS & SOPs',
    'pre-start': 'Pre-Start Checklist',
    'post-task': 'Post-Task Report',
    training: 'Training Assessment',
    permits: 'Permits to Work',
    incidents: 'Incident Reporting',
    inspections: 'Inspections',
    toolbox: 'Toolbox Meetings',
    'team-directory': 'Team Directory',
    compliance: 'Compliance Dashboard',
    assets: 'Asset Register',
    contractors: 'Contractor Management',
    'business-identity': 'Business Identity',
};

export default function PreviewPlaceholder() {
    const { url } = usePage();
    const slug = url.split('/').pop() ?? '';
    const title = titles[slug] ?? 'Preview';

    return (
        <>
            <Head title={`${title} · OpsFortress Preview`} />
            <div className="min-h-screen bg-slate-50 text-slate-900">
                <header className="sticky top-0 z-10 border-b border-slate-200 bg-white">
                    <div className="mx-auto flex h-14 max-w-5xl items-center gap-3 px-4">
                        <Link
                            href="/preview"
                            aria-label="Back"
                            className="rounded-md p-2 text-slate-600 hover:bg-slate-100"
                        >
                            <ArrowLeft className="size-5" />
                        </Link>
                        <div className="flex items-center gap-2">
                            <span className="grid size-7 place-items-center rounded-md bg-[#22c55e] text-white">
                                <ShieldCheck className="size-4" />
                            </span>
                            <span className="text-base font-semibold tracking-tight">
                                OpsFortress
                            </span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto max-w-3xl px-4 py-10">
                    <p className="text-xs font-medium uppercase tracking-wide text-[#22c55e]">
                        Preview
                    </p>
                    <h1 className="mt-1 text-3xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <p className="mt-3 text-sm text-slate-500">
                        Static placeholder. The schema and seeders for this
                        module land in M7–M8; the interactive flow lands in M9.
                    </p>

                    <div className="mt-8 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
                        <p className="text-sm text-slate-500">
                            Screen content goes here.
                        </p>
                    </div>

                    <div className="mt-8">
                        <Link
                            href="/preview"
                            className="inline-flex items-center gap-2 text-sm font-medium text-[#22c55e] hover:underline"
                        >
                            <ArrowLeft className="size-4" />
                            Back to Home
                        </Link>
                    </div>
                </main>
            </div>
        </>
    );
}
