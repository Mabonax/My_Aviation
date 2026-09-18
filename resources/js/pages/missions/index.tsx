import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { MapPin, ArrowRight, Plus } from 'lucide-react';
import { MissionProfile } from './types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Missions', href: '/missions' }];

export default function Index({ missions }: { missions: MissionProfile[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Missions" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Missions"
                    description="FR-MIS-001 mission planning records with lifecycle state and release-gate evidence."
                    actions={
                        <Button asChild>
                            <Link href="/missions/create">
                                New mission
                                <Plus />
                            </Link>
                        </Button>
                    }
                />

                <section className="rounded-lg border bg-card text-card-foreground shadow-xs">
                    {missions.length === 0 ? (
                        <EmptyState icon={MapPin} title="No missions yet" description="Create the first mission plan to start Phase 2 operations control." action={<Link href="/missions/create">Create mission</Link>} />
                    ) : (
                        <div className="divide-y">
                            {missions.map((mission) => (
                                <div key={mission.id} className="grid gap-3 px-4 py-4 md:grid-cols-12 md:items-center">
                                    <div className="md:col-span-4">
                                        <div className="font-medium">{mission.mission_number}</div>
                                        <div className="text-sm text-muted-foreground">{mission.purpose}</div>
                                    </div>
                                    <div className="text-sm text-muted-foreground md:col-span-3">{mission.location}</div>
                                    <div className="text-sm text-muted-foreground md:col-span-2">
                                        {mission.operator?.legal_entity || 'No operator'}
                                        <div className="text-xs">{mission.compliance.blocking_count} block / {mission.compliance.warning_count} warn</div>
                                    </div>
                                    <div className="md:col-span-1"><StatusBadge value={mission.lifecycle_state.replaceAll('_', ' ')} /></div>
                                    <div className="md:col-span-1"><StatusBadge value={mission.compliance.status} /></div>
                                    <div className="flex justify-end md:col-span-1">
                                        <Button variant="ghost" size="icon" asChild aria-label={`View ${mission.mission_number}`}>
                                            <Link href={`/missions/${mission.id}`}><ArrowRight className="size-4" /></Link>
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
