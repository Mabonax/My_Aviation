import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { MetricCard } from '@/components/uas/metric-card';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BookOpen, ClipboardCheck, FileText, Gauge, ListChecks, NotebookTabs, PlaneTakeoff, UserRound } from 'lucide-react';
import { type ReactNode } from 'react';
import { MyPilotWorkspace } from './types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'My Pilot', href: '/my/pilot' }];

export default function Show({ workspace }: { workspace: MyPilotWorkspace | null }) {
    if (!workspace) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="My Pilot" />
                <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                    <PageHeader title="My pilot workspace" description="Your pilot identity anchors compliance, missions, logbook and future field access." />
                    <EmptyState
                        icon={UserRound}
                        title="Complete your pilot profile"
                        description="Set up your pilot identity to track RPC, medical, ratings, compliance, missions and logbook records."
                        action={<Link href="/my/pilot/create">Create Pilot Profile</Link>}
                    />
                </div>
            </AppLayout>
        );
    }

    const { pilot, compliance, logbook, documents, missions, certificates } = workspace;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Pilot" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="My pilot workspace"
                    description={pilot.sacaa_certificate_number || 'SACAA certificate not captured'}
                    actions={
                        <>
                            <Button asChild>
                                <Link href="/my/pilot/edit">Edit profile</Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href="/my/compliance">My compliance</Link>
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard title="Profile status" value={compliance.profile_status.replace('_', ' ')} status={compliance.profile_status} icon={UserRound} />
                    <MetricCard title="Certificates" value={compliance.certificate_count.toString()} status={compliance.expiring_certificates_30_days > 0 ? 'Attention' : 'Current'} icon={ClipboardCheck} />
                    <MetricCard title="Logbook hours" value={logbook.total_hours.toString()} status={`${logbook.entry_count} entries`} icon={NotebookTabs} />
                    <MetricCard title="Documents" value={documents.count.toString()} status="Evidence" icon={FileText} />
                </div>

                <div className="grid gap-6 xl:grid-cols-[2fr_1fr]">
                    <section className="space-y-6">
                        <Panel title="Pilot Identity">
                            <Detail label="Name" value={pilot.display_name} />
                            <Detail label="Preferred name" value={pilot.preferred_name} />
                            <Detail label="Email" value={pilot.email} />
                            <Detail label="Phone" value={pilot.phone} />
                            <Detail label="Nationality" value={pilot.nationality} />
                            <Detail label="Date of birth" value={pilot.date_of_birth} />
                        </Panel>

                        <Panel title="Compliance Snapshot">
                            <Detail label="RPC category" value={compliance.rpc_category.replace('_', ' ')} />
                            <Detail label="Medical" value={compliance.medical_status.replace('_', ' ')} />
                            <Detail label="Radiotelephony" value={compliance.radiotelephony_qualification.replace('_', ' ')} />
                            <Detail label="Ratings" value={pilot.ratings.length ? pilot.ratings.join(', ') : null} />
                        </Panel>

                        <Panel title="Recent Missions">
                            {missions.length ? (
                                <div className="grid gap-3 sm:col-span-2">
                                    {missions.map((mission) => (
                                        <div key={mission.id} className="rounded-md border p-3">
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <p className="font-medium">{mission.mission_number}</p>
                                                {mission.release_gate_state && <StatusBadge value={mission.release_gate_state} />}
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">{mission.purpose} at {mission.location}</p>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground sm:col-span-2">No pilot-linked missions found yet.</p>
                            )}
                        </Panel>
                    </section>

                    <aside className="space-y-6">
                        <Panel title="Next Actions">
                            <Shortcut href="/my/compliance" icon={Gauge} label="Open compliance" />
                            <Shortcut href="/missions" icon={PlaneTakeoff} label="Open missions" />
                            <Shortcut href="/training-courses" icon={BookOpen} label="Open learning" />
                            <Shortcut href="/pilots" icon={ListChecks} label="Admin pilot register" />
                        </Panel>

                        <Panel title="Certificates">
                            {certificates.length ? certificates.map((certificate) => (
                                <div key={certificate.id} className="rounded-md border p-3">
                                    <p className="font-medium">{certificate.certificate_number}</p>
                                    <p className="mt-1 text-sm text-muted-foreground">{certificate.status} · expires {certificate.expiry_date || 'not captured'}</p>
                                </div>
                            )) : <p className="text-sm text-muted-foreground">No certificates linked yet.</p>}
                        </Panel>
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
            <h2 className="mb-4 text-sm font-semibold uppercase tracking-normal text-muted-foreground">{title}</h2>
            <div className="grid gap-4 sm:grid-cols-2">{children}</div>
        </section>
    );
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return (
        <div>
            <dt className="text-xs font-medium uppercase tracking-normal text-muted-foreground">{label}</dt>
            <dd className="mt-1 break-words text-sm capitalize">{value || 'Not captured'}</dd>
        </div>
    );
}

function Shortcut({ href, icon: Icon, label }: { href: string; icon: typeof UserRound; label: string }) {
    return (
        <Button variant="outline" asChild>
            <Link href={href} className="justify-start">
                <Icon />
                {label}
            </Link>
        </Button>
    );
}
