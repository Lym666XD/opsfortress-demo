import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    BookOpenCheck,
    Boxes,
    Building2,
    ChevronRight,
    ClipboardCheck,
    ClipboardList,
    FileBadge,
    GraduationCap,
    HardHat,
    LayoutGrid,
    type LucideIcon,
    MapPin,
    Menu,
    Package,
    RefreshCw,
    Search,
    ShieldCheck,
    Users,
} from 'lucide-react';

type Tile = {
    title: string;
    description: string;
    slug: string;
    Icon: LucideIcon;
};

const tiles: Tile[] = [
    {
        title: 'Workplace Sign-In',
        description: 'Geo or QR check-in to load your assigned tasks',
        slug: 'workplace-sign-in',
        Icon: MapPin,
    },
    {
        title: 'My Task Pack',
        description: 'SWMS, SOPs and checks matched to your occupation',
        slug: 'my-task-pack',
        Icon: LayoutGrid,
    },
    {
        title: 'SWMS & SOPs',
        description: 'Browse safe-work and standard operating procedures',
        slug: 'swms-sops',
        Icon: BookOpenCheck,
    },
    {
        title: 'Pre-Start Checklist',
        description: '15-point pre-start with critical-fail gating',
        slug: 'pre-start',
        Icon: ClipboardCheck,
    },
    {
        title: 'Post-Task Report',
        description: '15-point post-task closure with corrective actions',
        slug: 'post-task',
        Icon: ClipboardList,
    },
    {
        title: 'Training Assessment',
        description: '12-question competency assessment, ≥80% to pass',
        slug: 'training',
        Icon: GraduationCap,
    },
    {
        title: 'Permits to Work',
        description: 'Hot Work permits with fire-danger gating',
        slug: 'permits',
        Icon: FileBadge,
    },
    {
        title: 'Incident Reporting',
        description: 'Investigation form with multi-party team capture',
        slug: 'incidents',
        Icon: AlertTriangle,
    },
    {
        title: 'Inspections',
        description: 'Workplace, plant, scaffold and vehicle inspections',
        slug: 'inspections',
        Icon: ShieldCheck,
    },
    {
        title: 'Toolbox Meetings',
        description: 'Consultation, meeting agendas and attendance',
        slug: 'toolbox',
        Icon: Users,
    },
    {
        title: 'Team Directory',
        description: 'Workers, occupations and 3-level org hierarchy',
        slug: 'team-directory',
        Icon: HardHat,
    },
    {
        title: 'Compliance Dashboard',
        description: 'Green / Amber / Red status across the business',
        slug: 'compliance',
        Icon: BarChart3,
    },
    {
        title: 'Asset Register',
        description: 'Plant, equipment and tools across 13 categories',
        slug: 'assets',
        Icon: Package,
    },
    {
        title: 'Contractor Management',
        description: 'Capability assessments for contractors and PCBUs',
        slug: 'contractors',
        Icon: Boxes,
    },
    {
        title: 'Business Identity',
        description: 'Onboarding, ABN, branding and partnership structure',
        slug: 'business-identity',
        Icon: Building2,
    },
];

export default function PreviewHome() {
    return (
        <>
            <Head title="OpsFortress Preview" />
            <div className="min-h-screen bg-slate-50 text-slate-900">
                <header className="sticky top-0 z-10 border-b border-slate-200 bg-white">
                    <div className="mx-auto flex h-14 max-w-5xl items-center gap-3 px-4">
                        <button
                            type="button"
                            aria-label="Menu"
                            className="rounded-md p-2 text-slate-600 hover:bg-slate-100"
                        >
                            <Menu className="size-5" />
                        </button>
                        <div className="flex items-center gap-2">
                            <span className="grid size-7 place-items-center rounded-md bg-[#22c55e] text-white">
                                <ShieldCheck className="size-4" />
                            </span>
                            <span className="text-base font-semibold tracking-tight">
                                OpsFortress
                            </span>
                        </div>
                        <div className="ml-auto flex items-center gap-1">
                            <button
                                type="button"
                                aria-label="Search"
                                className="rounded-md p-2 text-slate-600 hover:bg-slate-100"
                            >
                                <Search className="size-5" />
                            </button>
                            <button
                                type="button"
                                aria-label="Refresh"
                                className="rounded-md p-2 text-slate-600 hover:bg-slate-100"
                            >
                                <RefreshCw className="size-5" />
                            </button>
                        </div>
                    </div>
                </header>

                <main className="mx-auto max-w-5xl px-4 py-6">
                    <div className="mb-5">
                        <p className="text-xs font-medium uppercase tracking-wide text-[#22c55e]">
                            Preview
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight">
                            Home
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Pick a workflow to explore. This is a static
                            preview — wiring to live data lands next.
                        </p>
                    </div>

                    <ul className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                        {tiles.map((tile) => (
                            <li key={tile.slug}>
                                <Link
                                    href={`/preview/${tile.slug}`}
                                    className="group flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#22c55e]/40 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[#22c55e]"
                                >
                                    <span className="grid size-12 shrink-0 place-items-center rounded-lg bg-[#22c55e]/10 text-[#22c55e]">
                                        <tile.Icon className="size-6" />
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-sm font-semibold text-slate-900">
                                            {tile.title}
                                        </span>
                                        <span className="mt-0.5 block truncate text-xs text-slate-500">
                                            {tile.description}
                                        </span>
                                    </span>
                                    <ChevronRight className="size-4 shrink-0 text-slate-400 transition group-hover:text-[#22c55e]" />
                                </Link>
                            </li>
                        ))}
                    </ul>
                </main>
            </div>
        </>
    );
}
