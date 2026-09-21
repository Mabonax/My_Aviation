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
    const readinessScore = Math.round(([profileReady, medicalReady, expiring === 0].filter(Boolean).length / 3) * 100);
    const operator = operatorWorkspace?.active_operator;
    const formatDate = (value?: string | null) => value ? new Date(value).toLocaleDateString('en-ZA', { day: '2-digit', month: 'short', year: 'numeric' }) : 'Not supplied';

    return (
        <div className="min-h-screen bg-[#f4f9fd] text-[#102b4e]">
            <section className="relative min-h-[188px] overflow-hidden border-b border-[#d9e8f5] bg-white">
                <img src="/yaw-dashboard-alpine.svg" alt="" className="absolute inset-0 h-full w-full object-cover object-right" />
                <div className="absolute inset-0 bg-gradient-to-r from-white via-white/75 to-white/10" />
                <div className="relative flex min-h-[188px] flex-col justify-between gap-5 px-7 py-6 xl:flex-row xl:items-center xl:px-10">
                    <div>
                        <p className="text-[20px] font-medium text-[#17345d]">Good afternoon,</p>
                        <h1 className="text-[48px] font-extrabold leading-none tracking-[-0.04em] text-[#09264b]">{firstName}</h1>
                        <p className="mt-2 text-[17px] font-medium text-[#164a89]">Your flight readiness and operational work queue</p>
                    </div>
                    <div className="hidden text-center text-[12px] font-bold uppercase tracking-[0.45em] text-[#315b8a] 2xl:block">
                        <p>People</p><p className="mt-1">Compliance</p><p className="mt-1">Safer Skies</p>
                    </div>
                    <div className="flex flex-wrap gap-3 self-start xl:self-center">
                        <Button variant="outline" className="h-12 border-[#1682e8] bg-white/90 px-5 text-[#0874d7] shadow-sm" asChild><Link href="/my/pilot"><UserRound />My Pilot Profile</Link></Button>
                        <Button variant="outline" className="h-12 border-[#1682e8] bg-white/90 px-5 text-[#0874d7] shadow-sm" asChild><Link href="/my/compliance"><ShieldCheck />My Compliance</Link></Button>
                    </div>
                </div>
            </section>

            <div className="space-y-4 p-5 lg:p-6 xl:px-8">
                <section className="grid overflow-hidden rounded-xl border border-[#cfe3f5] bg-white shadow-[0_2px_12px_rgba(15,69,112,.05)] lg:grid-cols-[190px_1.15fr_.85fr] lg:items-center">
                    <div className="flex justify-center p-5">
                        <div className="relative grid size-32 place-items-center rounded-full" style={{ background: `conic-gradient(${readinessAttention ? '#e99a19' : '#079447'} ${readinessScore * 3.6}deg,#e7f0f6 0)` }}>
                            <div className="grid size-[104px] place-items-center rounded-full bg-white text-[28px] font-extrabold text-[#08265a]">{readinessScore}%</div>
                        </div>
                    </div>
                    <div className="px-5 py-6">
                        <p className="text-[15px] font-semibold text-[#345675]">Flight readiness</p>
                        <div className={`mt-1 flex items-center gap-2 text-[36px] font-extrabold leading-none ${readinessAttention ? 'text-[#c57a08]' : 'text-[#079447]'}`}>
                            <span className="grid size-10 place-items-center rounded-full bg-current"><Plane className="size-5 text-white" /></span>
                            {readinessAttention ? 'ACTION REQUIRED' : 'READY'}
                        </div>
                        <p className="mt-3 text-[16px] font-bold text-[#122f52]">{readinessAttention ? 'Resolve the items below before flight release.' : 'You are compliant and clear to fly'}</p>
                        <p className="mt-1 text-sm text-[#607793]">All mandatory pilot requirements are checked against your current records.</p>
                    </div>
                    <div className="border-t border-[#d8e6f1] px-8 py-7 lg:border-l lg:border-t-0">
                        <p className="font-serif text-[22px] font-semibold italic leading-snug text-[#345675]">“Compliant pilots<br/>make a safer tomorrow.”</p>
                        <div className="mt-4 h-[3px] w-12 bg-[#1682e8]" />
                        <p className="mt-4 text-[11px] font-bold uppercase tracking-[0.18em] text-[#1476cf]">Fly responsibly. Fly further.</p>
                    </div>
                </section>

                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <PilotStat icon={<UserRound />} title="Pilot profile" value={compliance.profile_status.replaceAll('_',' ')} detail="Personal pilot record" good={profileReady} href="/my/pilot" />
                    <PilotStat icon={<ShieldCheck />} title="Medical certificate" value={compliance.medical_status.replaceAll('_',' ')} detail="Verified compliance status" good={medicalReady} href="/my/compliance" />
                    <PilotStat icon={<ClipboardCheck />} title="Expiring in 30 days" value={String(expiring)} detail={expiring ? 'Items require attention' : 'No items require attention'} good={expiring === 0} href="/my/compliance" />
                    <PilotStat icon={<NotebookTabs />} title="Logbook hours" value={String(logbook.total_hours)} detail={`Across ${logbook.entry_count} entries`} good href="/my/pilot" />
                </div>

                <div className="grid gap-3 xl:grid-cols-[1.35fr_1fr]">
                    <section className={`rounded-xl border p-5 ${readinessAttention ? 'border-amber-200 bg-amber-50' : 'border-[#cdebdc] bg-[#f1fbf6]'}`}>
                        <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                            <div className="flex items-start gap-4">
                                <span className={`grid size-10 shrink-0 place-items-center rounded-full text-white ${readinessAttention ? 'bg-amber-500' : 'bg-[#079447]'}`}>{readinessAttention ? <AlertTriangle className="size-5"/> : <ShieldCheck className="size-5"/>}</span>
                                <div><h2 className="text-[16px] font-bold text-[#17345d]">Next action</h2><p className={`mt-1 font-bold ${readinessAttention ? 'text-amber-700' : 'text-[#079447]'}`}>{readinessAttention ? 'Review compliance items' : 'No action required'}</p><p className="mt-1 text-sm text-[#607793]">{readinessAttention ? 'Open your compliance record and resolve outstanding controls.' : 'You are compliant and have no outstanding items.'}</p></div>
                            </div>
                            <Button variant="outline" className="border-[#1682e8] bg-white text-[#0874d7]" asChild><Link href="/my/compliance">View My Compliance<ArrowRight /></Link></Button>
                        </div>
                    </section>
                    <section className="rounded-xl border border-[#d5e4f1] bg-white p-5 shadow-[0_2px_10px_rgba(15,69,112,.04)]">
                        <div className="flex items-center justify-between"><h2 className="flex items-center gap-2 font-bold text-[#17345d]"><UserRound className="size-5 text-[#0874d7]"/>Operator context</h2>{operator && <span className="rounded bg-[#dcf6e7] px-2 py-1 text-[10px] font-extrabold text-[#078644]">APPROVED</span>}</div>
                        <p className="mt-3 font-bold text-[#17345d]">{operator?.name || 'Personal pilot workspace'}</p>
                        <div className="mt-3 grid grid-cols-3 divide-x text-xs"><div><p className="text-[#71869b]">Role</p><p className="mt-1 font-bold">Pilot</p></div><div className="pl-4"><p className="text-[#71869b]">Your status</p><p className="mt-1 font-bold">{operator ? 'Active' : 'Personal'}</p></div><div className="pl-4"><p className="text-[#71869b]">UASOC</p><p className="mt-1 font-bold">{operator?.uasoc_number || '—'}</p></div></div>
                    </section>
                </div>

                <div className="grid gap-3 xl:grid-cols-[1.65fr_.95fr]">
                    <section className="rounded-xl border border-[#d5e4f1] bg-white p-5 shadow-[0_2px_10px_rgba(15,69,112,.04)]">
                        <div className="flex items-center justify-between"><h2 className="flex items-center gap-2 text-[17px] font-bold"><Plane className="size-5 text-[#0874d7]"/>Assigned missions</h2><Link href="/missions" className="flex items-center gap-2 text-sm font-bold text-[#0874d7]">View all missions<ArrowRight className="size-4"/></Link></div>
                        <div className="mt-4 overflow-x-auto">
                            {missions.length === 0 ? <p className="py-8 text-sm text-[#71869b]">No assigned missions are currently available.</p> :
                            <table className="w-full min-w-[660px] text-left text-[12px]"><thead className="bg-[#f1f5f8] text-[#526c86]"><tr><th className="rounded-l-md p-3">#</th><th className="p-3">Purpose</th><th className="p-3">Planned date</th><th className="p-3">State</th><th className="rounded-r-md p-3">Compliance</th></tr></thead><tbody>{missions.slice(0,5).map(m=><tr key={m.id} className="border-b border-[#edf2f6]"><td className="p-3 font-bold text-[#315b8a]"><Link href={`/missions/${m.id}`}>{m.mission_number}</Link></td><td className="p-3">{m.purpose}</td><td className="p-3">{formatDate(m.planned_start_at)}</td><td className="p-3 capitalize"><span className="mr-2 inline-block size-2 rounded-full bg-[#1682e8]"/>{m.lifecycle_state?.replaceAll('_',' ') || 'Draft'}</td><td className="p-3 font-semibold text-[#079447]">● {m.release_gate_state === 'red' ? 'Attention' : 'Compliant'}</td></tr>)}</tbody></table>}
                        </div>
                    </section>
                    <section className="rounded-xl border border-[#d5e4f1] bg-white p-5 shadow-[0_2px_10px_rgba(15,69,112,.04)]">
                        <div className="flex items-center justify-between"><h2 className="flex items-center gap-2 text-[17px] font-bold"><ShieldCheck className="size-5 text-[#0874d7]"/>Credentials & expiries</h2><Link href="/my/compliance" className="text-sm font-bold text-[#0874d7]">View all →</Link></div>
                        <div className="mt-4">
                            {certificates.length === 0 ? <p className="text-sm text-[#71869b]">No certificate records available.</p> : certificates.slice(0,5).map((certificate,i)=>{
                                const warn=certificate.days_until_expiry !== null && certificate.days_until_expiry <= 30;
                                return <div key={certificate.id} className="relative flex gap-4 pb-4 last:pb-0"><div className="relative flex w-3 justify-center"><span className={`z-10 mt-1 size-3 rounded-full ${warn ? 'bg-[#f59e0b]' : 'bg-[#079447]'}`}/>{i<Math.min(certificates.length,5)-1 && <span className="absolute top-3 h-full w-px bg-[#d5e4f1]"/>}</div><div className="flex flex-1 items-start justify-between gap-3"><div><p className="text-[13px] font-bold">{certificate.certificate_number}</p><p className="mt-0.5 text-[12px] text-[#607793]">{certificate.expiry_date ? `Valid until ${formatDate(certificate.expiry_date)}` : 'No expiry supplied'}</p></div><span className={`rounded px-2 py-1 text-[10px] font-extrabold uppercase ${warn ? 'bg-[#fff0d0] text-[#d67b00]' : 'bg-[#dcf6e7] text-[#078644]'}`}>{warn ? 'Expiring' : certificate.status}</span></div></div>
                            })}
                        </div>
                    </section>
                </div>
            </div>
        </div>
    );
}

function PilotStat({icon,title,value,detail,good,href}:{icon:React.ReactNode;title:string;value:string;detail:string;good:boolean;href:string}) {
    return <Link href={href} className="group flex min-h-[112px] items-center gap-4 rounded-xl border border-[#d5e4f1] bg-white p-5 shadow-[0_2px_10px_rgba(15,69,112,.04)] transition hover:border-[#1682e8]"><span className="grid size-12 shrink-0 place-items-center rounded-full bg-[#e7f4ff] text-[#0874d7] [&>svg]:size-6">{icon}</span><div className="min-w-0 flex-1"><p className="text-[13px] font-bold text-[#17345d]">{title}</p><p className={`mt-1 truncate text-[22px] font-extrabold uppercase leading-none ${good ? 'text-[#079447]' : 'text-[#d18308]'}`}>{value}</p><p className="mt-2 truncate text-[12px] text-[#607793]">{detail}</p></div><ArrowRight className="size-5 text-[#1682e8]"/></Link>
}
