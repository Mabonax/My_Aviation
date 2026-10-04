import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    ClipboardCheck,
    Clock3,
    FileWarning,
    Gauge,
    Plane,
    ShieldCheck,
    Wrench,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Operations', href: '/dashboard' }];

interface DashboardSummary {
    pilots: number;
    aircraft: number;
    certificates_expiring_30_days: number;
    open_findings: number;
    documents_missing_review: number;
}

interface JourneyStep {
    key: string;
    label: string;
    complete: boolean;
    blocking: boolean;
    action: string | null;
}

interface ActionItem {
    key: string;
    priority: 'critical' | 'warning' | 'info';
    title: string;
    summary: string;
    entity_type: string;
    entity_id: number | null;
    action_href: string | null;
    due_at: string | null;
}

interface Experience {
    persona: string;
    workspace: {
        type: 'personal' | 'operator';
        operator: { id: number; legal_entity: string; trading_name?: string | null; uasoc_number?: string | null } | null;
        membership_role: string | null;
    };
    onboarding: {
        complete: boolean;
        percentage: number;
        next_action: string | null;
        steps: JourneyStep[];
    };
    readiness: {
        state: 'green' | 'amber' | 'red';
        percentage: number;
        blocking_items: string[];
    };
    capabilities: Record<string, boolean>;
    action_centre: {
        summary: { total: number; critical: number; warning: number; info: number };
        items: ActionItem[];
    };
}

interface PipelineItem {
    key: string;
    label: string;
    count: number;
}

interface DashboardMission {
    id: number;
    mission_number: string;
    operation_name: string;
    pilot: string | null;
    aircraft: string | null;
    readiness: string;
    readiness_label: string;
    next_action: string;
    planned_start_at: string | null;
    lifecycle_state: string;
}

interface OperatorOverview {
    pipeline: PipelineItem[];
    fleet: {
        total: number;
        ready: number;
        review: number;
        grounded: number;
    };
    missions: DashboardMission[];
    certificate: {
        status: string | null;
        uasoc_number: string | null;
        expiry_date: string | null;
        days_remaining: number | null;
    } | null;
}

function humanize(value: string) {
    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function readinessTone(status: string) {
    if (status === 'green') return 'bg-emerald-100 text-emerald-700';
    if (status === 'red') return 'bg-rose-100 text-rose-700';
    return 'bg-amber-100 text-amber-700';
}

function priorityTone(priority: string) {
    if (priority === 'critical') return 'bg-rose-50 text-rose-700 border-rose-100';
    if (priority === 'warning') return 'bg-amber-50 text-amber-700 border-amber-100';
    return 'bg-sky-50 text-sky-700 border-sky-100';
}

function formattedTime(value: string | null) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '—';
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

export default function Dashboard({
    summary,
    experience,
    operatorOverview,
}: {
    summary: DashboardSummary;
    experience: Experience;
    operatorOverview: OperatorOverview;
}) {
    const { auth, operatorWorkspace } = usePage<{
        auth: { user: { name?: string | null } | null };
        operatorWorkspace: {
            active_operator: { id: number; name: string; operator_code?: string | null } | null;
            requires_selection: boolean;
            can_manage: boolean;
        } | null;
    }>().props;

    const firstName = auth?.user?.name?.split(' ')[0] || 'Operator';
    const isOperator = experience.workspace.type === 'operator';
    const operatorName =
        experience.workspace.operator?.trading_name ||
        experience.workspace.operator?.legal_entity ||
        operatorWorkspace?.active_operator?.name ||
        'Operator workspace';

    const pipelineByKey = Object.fromEntries(operatorOverview.pipeline.map((item) => [item.key, item.count]));
    const readyMissions = pipelineByKey.ready_for_flight ?? 0;
    const awaitingReview = (pipelineByKey.compliance_review ?? 0) + (pipelineByKey.awaiting_approval ?? 0);
    const fleet = operatorOverview.fleet;
    const totalFleet = Math.max(fleet.total, 1);
    const readyPct = Math.round((fleet.ready / totalFleet) * 100);
    const reviewPct = Math.round((fleet.review / totalFleet) * 100);
    const groundedPct = Math.max(0, 100 - readyPct - reviewPct);
    const actionItems = experience.action_centre?.items ?? [];

    if (!isOperator) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="My readiness" />
                <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                    <section className="overflow-hidden rounded-3xl border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-blue-50 p-6 shadow-sm">
                        <p className="text-sm font-medium text-sky-700">Personal Pilot</p>
                        <h1 className="mt-1 text-3xl font-semibold tracking-tight">Good afternoon, {firstName}</h1>
                        <p className="mt-2 text-sm text-slate-500">Your pilot readiness, credentials and operator relationships.</p>
                        <div className="mt-6 h-2 overflow-hidden rounded-full bg-slate-200">
                            <div className="h-full rounded-full bg-gradient-to-r from-sky-500 to-emerald-400" style={{ width: `${experience.readiness.percentage}%` }} />
                        </div>
                        <div className="mt-2 flex justify-between text-xs text-slate-500">
                            <span>{humanize(experience.readiness.state)}</span>
                            <span>{experience.readiness.percentage}% ready</span>
                        </div>
                    </section>

                    <section className="rounded-2xl border bg-white p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Action Centre</p>
                                <h2 className="mt-1 text-xl font-semibold">What needs your attention</h2>
                            </div>
                            <span className="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                                {experience.action_centre?.summary.critical ?? 0} critical
                            </span>
                        </div>
                        <div className="mt-4 space-y-3">
                            {actionItems.length === 0 ? (
                                <div className="rounded-xl border border-dashed p-6 text-center text-sm text-slate-500">No outstanding readiness actions.</div>
                            ) : (
                                actionItems.slice(0, 6).map((item) => (
                                    <div key={item.key} className="flex items-start gap-3 rounded-xl border p-4">
                                        <AlertTriangle className="mt-0.5 size-4 text-amber-500" />
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium">{item.title}</p>
                                            <p className="mt-1 text-sm text-slate-500">{item.summary}</p>
                                        </div>
                                        {item.action_href && (
                                            <Link href={item.action_href} className="text-xs font-semibold text-sky-700">
                                                Open
                                            </Link>
                                        )}
                                    </div>
                                ))
                            )}
                        </div>
                    </section>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Operations Dashboard" />
            <div className="flex flex-1 flex-col gap-4 bg-[#f4f8fc] p-4 text-slate-950 sm:p-5 xl:p-6">
                {operatorWorkspace?.requires_selection && (
                    <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Select an operator workspace before opening tenant operational records.
                    </div>
                )}

                <section className="relative overflow-hidden rounded-3xl border border-sky-100 bg-[radial-gradient(circle_at_75%_10%,rgba(125,211,252,.55),transparent_22%),linear-gradient(135deg,#eef8ff_0%,#f8fbff_52%,#e4f3ff_100%)] px-6 py-7 shadow-[0_18px_55px_-35px_rgba(15,23,42,.35)]">
                    <div className="absolute right-10 top-5 hidden h-28 w-56 rounded-full bg-sky-200/30 blur-3xl lg:block" />
                    <div className="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-sky-700">{operatorName}</p>
                            <h1 className="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Good afternoon, {firstName}</h1>
                            <p className="mt-1 text-base text-slate-600">{humanize(experience.persona)}</p>
                        </div>
                        <div className="flex items-center gap-3 rounded-2xl border border-white/80 bg-white/65 px-4 py-3 shadow-sm backdrop-blur">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                                <Gauge className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">Workspace</p>
                                <p className="text-sm font-semibold">{experience.workspace.operator?.uasoc_number || 'Operator context active'}</p>
                            </div>
                        </div>
                    </div>

                    <div className="relative mt-6 overflow-hidden rounded-2xl bg-gradient-to-r from-[#07385e] via-[#07558b] to-[#0c6fa5] p-5 text-white shadow-xl shadow-sky-900/10">
                        <div className="absolute inset-0 bg-[radial-gradient(circle_at_84%_0%,rgba(125,211,252,.35),transparent_26%)]" />
                        <div className="relative grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
                            <div>
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <p className="text-xl font-semibold">Operational Readiness</p>
                                        <p className="mt-1 text-sm text-sky-100">Overall readiness across missions, aircraft, compliance and crew.</p>
                                    </div>
                                    <p className="text-4xl font-semibold tracking-tight">{experience.readiness.percentage}%</p>
                                </div>
                                <div className="mt-5 h-2 overflow-hidden rounded-full bg-white/20">
                                    <div className="h-full rounded-full bg-gradient-to-r from-emerald-400 to-cyan-300" style={{ width: `${experience.readiness.percentage}%` }} />
                                </div>
                                <div className="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-sky-50">
                                    <span className="inline-flex items-center gap-1.5"><CheckCircle2 className="size-3.5 text-emerald-300" /> Fleet {fleet.total ? readyPct : 0}%</span>
                                    <span className="inline-flex items-center gap-1.5"><CheckCircle2 className="size-3.5 text-emerald-300" /> Missions {readyMissions}</span>
                                    <span className="inline-flex items-center gap-1.5"><CheckCircle2 className="size-3.5 text-emerald-300" /> Compliance {summary.open_findings === 0 ? 'Clear' : `${summary.open_findings} open`}</span>
                                    <span className="inline-flex items-center gap-1.5"><CheckCircle2 className="size-3.5 text-emerald-300" /> Evidence {summary.documents_missing_review === 0 ? 'Current' : 'Review'}</span>
                                </div>
                            </div>
                            <div className="hidden min-w-52 border-l border-white/20 pl-6 lg:block">
                                <Plane className="mb-3 size-8 text-cyan-200" />
                                <p className="text-sm font-semibold">Safe Operations</p>
                                <p className="text-sm text-sky-100">Enable greater possibilities.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    {[
                        { label: 'Ready Missions', value: readyMissions, icon: ClipboardCheck, tone: 'bg-emerald-50 text-emerald-600' },
                        { label: 'Missions Awaiting Review', value: awaitingReview, icon: Clock3, tone: 'bg-amber-50 text-amber-600' },
                        { label: 'Aircraft Ready', value: fleet.ready, icon: Plane, tone: 'bg-sky-50 text-sky-600' },
                        { label: 'Open Findings', value: summary.open_findings, icon: AlertTriangle, tone: 'bg-rose-50 text-rose-600' },
                        { label: 'Expiring Certificates', value: summary.certificates_expiring_30_days, icon: FileWarning, tone: 'bg-violet-50 text-violet-600' },
                    ].map(({ label, value, icon: Icon, tone }) => (
                        <div key={label} className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_12px_35px_-28px_rgba(15,23,42,.4)]">
                            <div className="flex items-start justify-between gap-3">
                                <div className={`flex size-11 items-center justify-center rounded-2xl ${tone}`}><Icon className="size-5" /></div>
                                <ArrowRight className="size-4 text-slate-300" />
                            </div>
                            <p className="mt-4 text-2xl font-semibold">{value}</p>
                            <p className="mt-0.5 text-xs font-medium text-slate-500">{label}</p>
                        </div>
                    ))}
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.35fr_1fr_.8fr]">
                    <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                        <div className="flex items-center justify-between gap-3">
                            <div className="flex items-center gap-2">
                                <h2 className="text-lg font-semibold">Action Centre</h2>
                                {experience.action_centre.summary.critical > 0 && (
                                    <span className="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                        {experience.action_centre.summary.critical} urgent
                                    </span>
                                )}
                            </div>
                            <span className="text-xs font-semibold text-sky-700">Prioritised by YAW</span>
                        </div>
                        <div className="mt-3 divide-y divide-slate-100">
                            {actionItems.length === 0 ? (
                                <div className="py-8 text-center text-sm text-slate-500">No outstanding operator actions.</div>
                            ) : (
                                actionItems.slice(0, 5).map((item) => (
                                    <div key={item.key} className="flex items-start gap-3 py-3">
                                        <div className={`mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg border ${priorityTone(item.priority)}`}>
                                            {item.entity_type === 'aircraft' ? <Wrench className="size-4" /> : item.entity_type === 'mission' ? <Plane className="size-4" /> : <AlertTriangle className="size-4" />}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-semibold">{item.title}</p>
                                            <p className="mt-0.5 line-clamp-1 text-xs text-slate-500">{item.summary}</p>
                                        </div>
                                        <span className={`rounded-full border px-2 py-1 text-[11px] font-semibold capitalize ${priorityTone(item.priority)}`}>{item.priority}</span>
                                        {item.action_href && <Link href={item.action_href} className="mt-1 text-sky-700"><ArrowRight className="size-4" /></Link>}
                                    </div>
                                ))
                            )}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Mission Journey</h2>
                            <Link href="/missions" className="text-xs font-semibold text-sky-700">View pipeline</Link>
                        </div>
                        <div className="mt-6 grid grid-cols-7 gap-1">
                            {operatorOverview.pipeline.map((item, index) => (
                                <div key={item.key} className="text-center">
                                    <div className={`mx-auto flex size-9 items-center justify-center rounded-full text-sm font-semibold ${index === 4 ? 'bg-emerald-100 text-emerald-700' : index === 3 ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700'}`}>
                                        {item.count}
                                    </div>
                                    <p className="mt-2 text-[10px] leading-tight text-slate-500">{item.label}</p>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Fleet Readiness</h2>
                            <Link href="/aircraft" className="text-xs font-semibold text-sky-700">View fleet</Link>
                        </div>
                        <div className="mt-4 flex items-center gap-5">
                            <div
                                className="relative size-28 shrink-0 rounded-full"
                                style={{
                                    background: `conic-gradient(#34d399 0 ${readyPct}%, #fbbf24 ${readyPct}% ${readyPct + reviewPct}%, #f87171 ${readyPct + reviewPct}% 100%)`,
                                }}
                            >
                                <div className="absolute inset-[12px] flex flex-col items-center justify-center rounded-full bg-white">
                                    <span className="text-2xl font-semibold">{fleet.total}</span>
                                    <span className="text-[10px] text-slate-500">Aircraft</span>
                                </div>
                            </div>
                            <div className="min-w-0 flex-1 space-y-2 text-xs">
                                <div className="flex justify-between"><span className="text-slate-500">Ready</span><span className="font-semibold">{fleet.ready} · {readyPct}%</span></div>
                                <div className="flex justify-between"><span className="text-slate-500">Review</span><span className="font-semibold">{fleet.review} · {reviewPct}%</span></div>
                                <div className="flex justify-between"><span className="text-slate-500">Grounded</span><span className="font-semibold">{fleet.grounded} · {groundedPct}%</span></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.55fr_.75fr]">
                    <div className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                        <div className="flex items-center justify-between px-5 py-4">
                            <h2 className="text-lg font-semibold">Today&apos;s Operations</h2>
                            <Link href="/missions" className="text-xs font-semibold text-sky-700">View all missions</Link>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[760px] text-left text-xs">
                                <thead className="bg-slate-50 text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3 font-medium">Mission</th>
                                        <th className="px-3 py-3 font-medium">Operation</th>
                                        <th className="px-3 py-3 font-medium">Pilot</th>
                                        <th className="px-3 py-3 font-medium">Aircraft</th>
                                        <th className="px-3 py-3 font-medium">Readiness</th>
                                        <th className="px-3 py-3 font-medium">Next Action</th>
                                        <th className="px-3 py-3 font-medium">ETA</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {operatorOverview.missions.length === 0 ? (
                                        <tr><td colSpan={7} className="px-5 py-8 text-center text-slate-500">No operator missions are available.</td></tr>
                                    ) : (
                                        operatorOverview.missions.map((mission) => (
                                            <tr key={mission.id} className="hover:bg-slate-50/70">
                                                <td className="px-5 py-3 font-semibold text-sky-700"><Link href={`/missions/${mission.id}`}>{mission.mission_number}</Link></td>
                                                <td className="px-3 py-3 font-medium">{mission.operation_name}</td>
                                                <td className="px-3 py-3 text-slate-500">{mission.pilot || 'Unassigned'}</td>
                                                <td className="px-3 py-3 text-slate-500">{mission.aircraft || 'Unassigned'}</td>
                                                <td className="px-3 py-3"><span className={`rounded-full px-2.5 py-1 text-[11px] font-semibold ${readinessTone(mission.readiness)}`}>{mission.readiness_label}</span></td>
                                                <td className="px-3 py-3 text-slate-600">{mission.next_action}</td>
                                                <td className="px-3 py-3 text-slate-500">{formattedTime(mission.planned_start_at)}</td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Compliance & Certification</h2>
                            <ShieldCheck className="size-5 text-emerald-500" />
                        </div>

                        <div className="mt-5 rounded-2xl bg-emerald-50 p-4">
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"><ShieldCheck className="size-5" /></div>
                                <div className="min-w-0">
                                    <p className="text-xs text-emerald-700">Operator certificate</p>
                                    <p className="truncate text-sm font-semibold">{operatorOverview.certificate?.uasoc_number || 'Not captured'}</p>
                                </div>
                            </div>
                            <div className="mt-4 flex items-end justify-between">
                                <div>
                                    <p className="text-[11px] text-slate-500">Expiry</p>
                                    <p className="text-sm font-medium">{operatorOverview.certificate?.expiry_date || 'Not captured'}</p>
                                </div>
                                {operatorOverview.certificate?.days_remaining !== null && operatorOverview.certificate?.days_remaining !== undefined && (
                                    <span className={`rounded-full px-2.5 py-1 text-[11px] font-semibold ${operatorOverview.certificate.days_remaining < 30 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'}`}>
                                        {operatorOverview.certificate.days_remaining} days
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="mt-5 space-y-3">
                            <div className="flex items-center justify-between border-b border-slate-100 pb-3 text-sm">
                                <span className="text-slate-500">Open findings</span>
                                <span className="font-semibold">{summary.open_findings}</span>
                            </div>
                            <div className="flex items-center justify-between border-b border-slate-100 pb-3 text-sm">
                                <span className="text-slate-500">Certificates expiring in 30 days</span>
                                <span className="font-semibold">{summary.certificates_expiring_30_days}</span>
                            </div>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-slate-500">Documents awaiting review</span>
                                <span className="font-semibold">{summary.documents_missing_review}</span>
                            </div>
                        </div>

                        <Link href="/compliance/register" className="mt-5 flex items-center justify-between rounded-xl border px-4 py-3 text-sm font-semibold hover:bg-slate-50">
                            Review compliance
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
