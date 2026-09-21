import { Button } from '@/components/ui/button';
import { MetricCard } from '@/components/uas/metric-card';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type MyPilotWorkspace } from '@/pages/my/pilot/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, ClipboardCheck, FileWarning, NotebookTabs, Plane, ShieldCheck, UserRound } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

interface DashboardSummary {
    pilots: number;
    aircraft: number;
    certificates_expiring_30_days: number;
    open_findings: number;
    documents_missing_review: number;
}

type OperatorWorkspace = {
    active_operator: { id: number; name: string; uasoc_number?: string | null } | null;
    requires_selection: boolean;
    can_manage: boolean;
} | null;

type UiCapabilities = {
    persona: 'platform_admin' | 'pilot' | 'operator_user' | 'user';
    pilot_self_service: boolean;
    operator_workspace: boolean;
    operator_manage: boolean;
    missions: boolean;
    aeronautical_information: boolean;
    platform_admin: boolean;
} | null;

export default function Dashboard({
    summary,
    pilotWorkspace,
    pilotOnboardingRequired,
}: {
    summary: DashboardSummary | null;
    pilotWorkspace: MyPilotWorkspace | null;
    pilotOnboardingRequired: boolean;
}) {
    const { operatorWorkspace, uiCapabilities } = usePage<{
        operatorWorkspace: OperatorWorkspace;
        uiCapabilities: UiCapabilities;
    }>().props;

    if (uiCapabilities?.pilot_self_service && !uiCapabilities.platform_admin) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="My Dashboard" />
                <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                    {operatorWorkspace?.requires_selection && (
                        <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                            Select an operator workspace before opening operator mission records.
                        </div>
                    )}

                    {pilotOnboardingRequired || !pilotWorkspace ? (
                        <>
                            <PageHeader
                                title="Complete your pilot profile"
                                description="Create your personal pilot record before using pilot compliance and operational features."
                                actions={<Button asChild><Link href="/my/pilot/create">Create pilot profile<ArrowRight /></Link></Button>}
                            />
                            <div className="rounded-xl border bg-card p-6 text-sm text-muted-foreground">
                                Your operator memberships do not replace your personal pilot profile. Operational access remains subject to operator membership and approval.
                            </div>
                        </>
                    ) : (
                        <PilotDashboard workspace={pilotWorkspace} operatorWorkspace={operatorWorkspace} />
                    )}
                </div>
            </AppLayout>
        );
    }

    const platformSummary = summary!;
    const phaseItems = [
        { title: 'Pilot profiles', value: platformSummary.pilots.toString(), status: 'Implemented', icon: UserRound },
        { title: 'Aircraft records', value: platformSummary.aircraft.toString(), status: 'Implemented', icon: Plane },
        { title: 'Expiring in 30 days', value: platformSummary.certificates_expiring_30_days.toString(), status: platformSummary.certificates_expiring_30_days > 0 ? 'Attention' : 'Valid', icon: AlertTriangle },
        { title: 'Open findings', value: platformSummary.open_findings.toString(), status: platformSummary.open_findings > 0 ? 'Attention' : 'Valid', icon: FileWarning },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                {operatorWorkspace?.requires_selection && <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">Select an operator workspace from the sidebar before opening tenant operational records.</div>}
                {operatorWorkspace?.active_operator && <div className="flex items-center justify-between rounded-xl border bg-card p-4"><div><p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Active operator workspace</p><p className="font-semibold">{operatorWorkspace.active_operator.name}</p></div><span className="text-xs text-muted-foreground">{operatorWorkspace.active_operator.uasoc_number}</span></div>}
                <PageHeader
                    title={uiCapabilities?.operator_workspace && !uiCapabilities?.platform_admin ? 'Operator workspace' : 'Phase 1 compliance workspace'}
                    description={uiCapabilities?.operator_workspace && !uiCapabilities?.platform_admin ? 'Operational records for your selected operator context.' : 'Pilot and fleet master records for the UAS compliance platform.'}
                    actions={uiCapabilities?.platform_admin ? <>
                        <Button asChild><Link href="/pilots">Open pilots<ArrowRight /></Link></Button>
                        <Button variant="outline" asChild><Link href="/phase-1/verification">Verify Phase 1<ShieldCheck /></Link></Button>
                    </> : undefined}
                />
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {phaseItems.map((item) => <MetricCard key={item.title} title={item.title} value={item.value} status={item.status} icon={item.icon} />)}
                </div>
            </div>
        </AppLayout>
    );
}

function PilotDashboard({ workspace, operatorWorkspace }: { workspace: MyPilotWorkspace; operatorWorkspace: OperatorWorkspace }) {
    const { pilot, compliance, logbook, missions } = workspace;
    const readinessAttention = compliance.medical_status !== 'valid' || compliance.profile_status !== 'active' || compliance.expiring_certificates_30_days > 0;

    return (
        <>
            <PageHeader
                title={`Welcome, ${pilot.preferred_name || pilot.first_name}`}
                description="Your personal pilot readiness, credentials and operational work queue."
                actions={<>
                    <Button asChild><Link href="/my/pilot">My pilot profile<ArrowRight /></Link></Button>
                    <Button variant="outline" asChild><Link href="/my/compliance">My compliance<ClipboardCheck /></Link></Button>
                </>}
            />

            {operatorWorkspace?.active_operator && (
                <div className="flex items-center justify-between rounded-xl border bg-card p-4">
                    <div><p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Active operator</p><p className="font-semibold">{operatorWorkspace.active_operator.name}</p></div>
                    <span className="text-xs text-muted-foreground">{operatorWorkspace.active_operator.uasoc_number}</span>
                </div>
            )}

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <MetricCard title="Profile" value={compliance.profile_status.replace('_', ' ')} status={readinessAttention ? 'Attention' : 'Ready'} icon={UserRound} />
                <MetricCard title="Medical" value={compliance.medical_status.replace('_', ' ')} status={compliance.medical_status === 'valid' ? 'Valid' : 'Attention'} icon={ShieldCheck} />
                <MetricCard title="Expiring in 30 days" value={compliance.expiring_certificates_30_days.toString()} status={compliance.expiring_certificates_30_days ? 'Attention' : 'Clear'} icon={AlertTriangle} />
                <MetricCard title="Logbook hours" value={logbook.total_hours.toString()} status={`${logbook.entry_count} entries`} icon={NotebookTabs} />
            </div>

            <section className="rounded-xl border bg-card p-5">
                <div className="flex items-center justify-between gap-4">
                    <div><h2 className="font-semibold">Assigned missions</h2><p className="text-sm text-muted-foreground">Your five most recent mission assignments.</p></div>
                    <Button variant="outline" asChild><Link href="/missions">Open missions</Link></Button>
                </div>
                <div className="mt-4 divide-y">
                    {missions.length === 0 ? <p className="py-4 text-sm text-muted-foreground">No assigned missions are currently available.</p> : missions.map((mission) => (
                        <Link key={mission.id} href={`/missions/${mission.id}`} className="flex items-center justify-between gap-4 py-3 text-sm hover:underline">
                            <span><strong>{mission.mission_number}</strong> · {mission.purpose}</span>
                            <span className="text-muted-foreground">{mission.lifecycle_state?.replaceAll('_', ' ')}</span>
                        </Link>
                    ))}
                </div>
            </section>
        </>
    );
}
