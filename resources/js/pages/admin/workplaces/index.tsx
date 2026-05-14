import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

// Workplace row shape mirrors the controller's index() projection.
// Kept narrow on purpose — list view shouldn't pull large columns like
// metadata JSON or audit timestamps.
type Workplace = {
    id: number;
    business_id: number;
    name: string;
    code: string | null;
    suburb: string | null;
    state: string | null;
    active: boolean;
    created_at: string;
};

type Props = {
    workplaces: Workplace[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Workplaces', href: '/admin/workplaces' },
];

export default function WorkplacesIndex({ workplaces }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Workplaces" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Workplaces"
                        description="Physical sites where workers sign in and perform tasks."
                    />
                    <Button asChild>
                        <Link href="/admin/workplaces/create">
                            Add Workplace
                        </Link>
                    </Button>
                </div>

                {workplaces.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                        No workplaces yet. Click "Add Workplace" to create the first one.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Name</th>
                                    <th className="px-4 py-3 font-medium">Code</th>
                                    <th className="px-4 py-3 font-medium">Suburb / State</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {workplaces.map((w) => (
                                    <tr key={w.id}>
                                        <td className="px-4 py-3 font-medium">
                                            {w.name}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {w.code ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {[w.suburb, w.state]
                                                .filter(Boolean)
                                                .join(', ') || '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={
                                                    w.active
                                                        ? 'rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-200'
                                                        : 'rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200'
                                                }
                                            >
                                                {w.active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
