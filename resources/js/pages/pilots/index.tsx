import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Eye, Plus, UserRound } from 'lucide-react';
import { PilotProfile } from './types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pilots', href: '/pilots' },
];

export default function Index({ pilots }: { pilots: PilotProfile[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pilot profiles" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Pilot profiles"
                    description="FR-PIL-001 master records for remote pilot identity, certificate and affiliation data."
                    actions={
                        <Button asChild>
                            <Link href="/pilots/create">
                                <Plus />
                                New pilot
                            </Link>
                        </Button>
                    }
                />

                <div className="overflow-hidden rounded-lg border bg-card text-card-foreground shadow-xs">
                    <div className="grid grid-cols-12 border-b bg-muted/50 px-4 py-3 text-xs font-medium uppercase text-muted-foreground">
                        <span className="col-span-4">Pilot</span>
                        <span className="col-span-3 hidden md:block">Certificate</span>
                        <span className="col-span-2 hidden lg:block">Category</span>
                        <span className="col-span-2">Status</span>
                        <span className="col-span-6 text-right md:col-span-3 lg:col-span-1">View</span>
                    </div>
                    {pilots.length === 0 ? (
                        <EmptyState
                            icon={UserRound}
                            title="No pilot profiles yet"
                            description="Create the first master record to start Phase 1 compliance tracking."
                            action={<Link href="/pilots/create">Create pilot</Link>}
                        />
                    ) : (
                        pilots.map((pilot) => (
                            <div key={pilot.id} className="grid grid-cols-12 items-center border-b px-4 py-3 text-sm last:border-b-0">
                                <div className="col-span-6 md:col-span-4">
                                    <div className="font-medium">{pilot.display_name}</div>
                                    <div className="text-muted-foreground">{pilot.email || pilot.employee_number || 'No contact reference'}</div>
                                </div>
                                <div className="col-span-3 hidden text-muted-foreground md:block">{pilot.sacaa_certificate_number || 'Unverified'}</div>
                                <div className="col-span-2 hidden text-muted-foreground lg:block">{pilot.rpc_category.replace('_', ' ')}</div>
                                <div className="col-span-3 md:col-span-2">
                                    <StatusBadge value={pilot.profile_status} />
                                </div>
                                <div className="col-span-3 text-right md:col-span-3 lg:col-span-1">
                                    <Button variant="ghost" size="icon" asChild aria-label={`View ${pilot.display_name}`}>
                                        <Link href={`/pilots/${pilot.id}`}>
                                            <Eye />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
