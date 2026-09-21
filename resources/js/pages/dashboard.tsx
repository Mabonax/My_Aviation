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
    const { pilot, compliance, logbook, missions, certificates } = workspace;
    const firstName = pilot.preferred_name || pilot.first_name;
    const profileReady = compliance.profile_status === 'active';
    const medicalReady = compliance.medical_status === 'valid';
    const expiring = compliance.expiring_certificates_30_days;
    const readinessAttention = !profileReady || !medicalReady || expiring > 0;
    const readinessScore = [profileReady, medicalReady, expiring === 0].filter(Boolean).length / 3 * 100;
    const operator = operatorWorkspace?.active_operator;
    const statusTone = readinessAttention ? 'text-amber-700' : 'text-emerald-700';

    return (
        <div className="space-y-5">
            <section className="relative overflow-hidden rounded-2xl border bg-gradient-to-r from-sky-50 via-white to-blue-50 p-6 shadow-sm">
                <div className="absolute inset-y-0 right-0 w-2/5 bg-[radial-gradient(circle_at_center,_rgba(14,165,233,0.16),_transparent_68%)]" />
                <div className="relative flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
                    <div>
                        <p className="text-sm font-medium text-slate-600">Good day,</p>
                        <h1 className="mt-1 text-4xl font-bold tracking-tight text-slate-950">{firstName}</h1>
                        <p className="mt-1 text-base text-slate-600">Your flight readiness and operational work queue</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild><Link href="/my/pilot"><UserRound />My Pilot Profile</Link></Button>
                        <Button variant="outline" asChild><Link href="/my/compliance"><ClipboardCheck />My Compliance</Link></Button>
                    </div>
                </div>
            </section>

            <section className="grid gap-5 rounded-2xl border bg-card p-6 shadow-sm lg:grid-cols-[220px_1fr_1fr] lg:items-center">
                <div className="flex justify-center">
                    <div className={`grid size-36 place-items-center rounded-full p-3 ${readinessAttention ? 'bg-amber-100' : 'bg-emerald-100'}`}>
                        <div className="grid size-full place-items-center rounded-full bg-white text-3xl font-bold text-slate-900 shadow-inner">{Math.round(readinessScore)}%</div>
                    </div>
                </div>
                <div>
                    <p className="text-sm font-semibold text-slate-600">Flight readiness</p>
                    <div className={`mt-1 flex items-center gap-2 text-3xl font-bold ${statusTone}`}>
                        <Plane className="size-7" />{readinessAttention ? 'ACTION REQUIRED' : 'READY'}
                    </div>
                    <p className="mt-2 font-semibold text-slate-900">{readinessAttention ? 'Resolve the items below before flight release.' : 'You are compliant and clear to fly.'}</p>
                    <p className="text-sm text-muted-foreground">Readiness reflects your profile, medical and certificate expiry controls.</p>
                </div>
                <div className="border-t pt-5 lg:border-l lg:border-t-0 lg:pl-8 lg:pt-0">
                    <p className="text-xl font-semibold italic text-slate-600">“Compliant pilots make a safer tomorrow.”</p>
                    <div className="mt-4 h-1 w-12 rounded bg-blue-600" />
                    <p className="mt-3 text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Fly responsibly. Fly future.</p>
                </div>
            </section>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <MetricCard title="Pilot profile" value={compliance.profile_status.replaceAll('_', ' ')} status={profileReady ? 'Active' : 'Attention'} icon={UserRound} />
                <MetricCard title="Medical certificate" value={compliance.medical_status.replaceAll('_', ' ')} status={medicalReady ? 'Valid' : 'Attention'} icon={ShieldCheck} />
                <MetricCard title="Expiring in 30 days" value={expiring.toString()} status={expiring ? 'Attention' : 'Clear'} icon={AlertTriangle} />
                <MetricCard title="Logbook hours" value={logbook.total_hours.toString()} status={`${logbook.entry_count} entries`} icon={NotebookTabs} />
            </div>

            <div className="grid gap-4 lg:grid-cols-[1.6fr_1fr]">
                <section className={`rounded-2xl border p-5 ${readinessAttention ? 'border-amber-200 bg-amber-50/70' : 'border-emerald-200 bg-emerald-50/70'}`}>
                    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div className="flex gap-3">
                            <div className={`grid size-10 place-items-center rounded-full ${readinessAttention ? 'bg-amber-600 text-white' : 'bg-emerald-600 text-white'}`}>
                                {readinessAttention ? <AlertTriangle className="size-5" /> : <ShieldCheck className="size-5" />}
                            </div>
                            <div><h2 className="font-semibold">Next action</h2><p className={`font-semibold ${statusTone}`}>{readinessAttention ? 'Review compliance items' : 'No action required'}</p><p className="text-sm text-muted-foreground">{readinessAttention ? 'Open your compliance record and resolve outstanding controls.' : 'You have no outstanding personal readiness items.'}</p></div>
                        </div>
                        <Button variant="outline" asChild><Link href="/my/compliance">View My Compliance<ArrowRight /></Link></Button>
                    </div>
                </section>

                <section className="rounded-2xl border bg-card p-5">
                    <div className="flex items-center justify-between"><h2 className="font-semibold">Operator context</h2>{operator && <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">ACTIVE</span>}</div>
                    {operator ? <><p className="mt-3 font-semibold">{operator.name}</p><p className="text-sm text-muted-foreground">{operator.uasoc_number || 'Selected operator workspace'}</p></> : <><p className="mt-3 font-semibold">Personal pilot workspace</p><p className="text-sm text-muted-foreground">Select an operator to access tenant missions and aircraft.</p></>}
                </section>
            </div>

            <div className="grid gap-4 xl:grid-cols-[1.8fr_1fr]">
                <section className="rounded-2xl border bg-card p-5 shadow-sm">
                    <div className="flex items-center justify-between gap-4">
                        <div><h2 className="flex items-center gap-2 font-semibold"><Plane className="size-5 text-blue-600" />Assigned missions</h2><p className="text-sm text-muted-foreground">Your five most recent mission assignments.</p></div>
                        <Button variant="ghost" asChild><Link href="/missions">View all<ArrowRight /></Link></Button>
                    </div>
                    <div className="mt-4 overflow-x-auto">
                        {missions.length === 0 ? <p className="py-6 text-sm text-muted-foreground">No assigned missions are currently available.</p> : (
                            <table className="w-full min-w-[620px] text-left text-sm">
                                <thead className="border-y bg-muted/40 text-xs text-muted-foreground"><tr><th className="p-3">Mission</th><th className="p-3">Purpose</th><th className="p-3">Planned</th><th className="p-3">State</th></tr></thead>
                                <tbody>{missions.map((mission) => <tr key={mission.id} className="border-b last:border-0"><td className="p-3 font-semibold"><Link className="hover:text-primary" href={`/missions/${mission.id}`}>{mission.mission_number}</Link></td><td className="p-3">{mission.purpose}</td><td className="p-3 text-muted-foreground">{mission.planned_start_at ? new Date(mission.planned_start_at).toLocaleDateString() : 'Not scheduled'}</td><td className="p-3 capitalize text-muted-foreground">{mission.lifecycle_state?.replaceAll('_', ' ') || 'Draft'}</td></tr>)}</tbody>
                            </table>
                        )}
                    </div>
                </section>

                <section className="rounded-2xl border bg-card p-5 shadow-sm">
                    <div className="flex items-center justify-between"><h2 className="flex items-center gap-2 font-semibold"><ShieldCheck className="size-5 text-blue-600" />Credentials & expiries</h2><Button variant="ghost" size="sm" asChild><Link href="/my/compliance">View all</Link></Button></div>
                    <div className="mt-4 space-y-3">
                        {certificates.length === 0 ? <p className="text-sm text-muted-foreground">No certificate records available.</p> : certificates.slice(0, 5).map((certificate) => {
                            const isExpiring = certificate.days_until_expiry !== null && certificate.days_until_expiry <= 30;
                            return <div key={certificate.id} className="flex items-start justify-between gap-3 border-b pb-3 last:border-0"><div><p className="text-sm font-semibold">{certificate.certificate_number}</p><p className="text-xs text-muted-foreground">{certificate.expiry_date ? `Valid until ${new Date(certificate.expiry_date).toLocaleDateString()}` : 'No expiry supplied'}</p></div><span className={`rounded-full px-2 py-1 text-[11px] font-bold uppercase ${isExpiring ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}`}>{isExpiring ? 'Expiring' : certificate.status}</span></div>;
                        })}
                    </div>
                </section>
            </div>
        </div>
    );
}
