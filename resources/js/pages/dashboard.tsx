import { Button } from '@/components/ui/button';
import { MetricCard } from '@/components/uas/metric-card';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, CheckCircle2, CircleAlert, FileWarning, Plane, ShieldCheck, UserRound } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

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
}

function humanize(value: string) {
    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export default function Dashboard({ summary, experience }: { summary: DashboardSummary; experience: Experience }) {
    const { operatorWorkspace } = usePage<{operatorWorkspace:{active_operator:{id:number;name:string;operator_code?:string|null}|null;requires_selection:boolean;can_manage:boolean}|null}>().props;

    const phaseItems = [
        { title: 'Pilot profiles', value: summary.pilots.toString(), status: 'Implemented', icon: UserRound },
        { title: 'Aircraft records', value: summary.aircraft.toString(), status: 'Implemented', icon: Plane },
        { title: 'Expiring in 30 days', value: summary.certificates_expiring_30_days.toString(), status: summary.certificates_expiring_30_days > 0 ? 'Attention' : 'Valid', icon: AlertTriangle },
        { title: 'Open findings', value: summary.open_findings.toString(), status: summary.open_findings > 0 ? 'Attention' : 'Valid', icon: FileWarning },
    ];

    const title = experience.workspace.type === 'operator'
        ? experience.workspace.operator?.trading_name || experience.workspace.operator?.legal_entity || 'Operator workspace'
        : 'My pilot workspace';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                {operatorWorkspace?.requires_selection && (
                    <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                        Select an operator workspace from the sidebar before opening tenant operational records.
                    </div>
                )}

                <PageHeader
                    title={title}
                    description={`${humanize(experience.persona)} · ${experience.readiness.percentage}% readiness`}
                    actions={
                        experience.onboarding.next_action ? (
                            <Button asChild>
                                <Link href={experience.onboarding.next_action}>
                                    Continue setup
                                    <ArrowRight />
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="outline" asChild>
                                <Link href="/my/compliance">
                                    Review compliance
                                    <ShieldCheck />
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="grid gap-4 lg:grid-cols-[1.2fr_1fr]">
                    <section className="rounded-2xl border bg-card p-5">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">Operational readiness</p>
                                <h2 className="mt-1 text-2xl font-semibold">{experience.readiness.percentage}% ready</h2>
                            </div>
                            <div className="text-right text-sm text-muted-foreground">
                                {experience.readiness.state === 'green' ? 'Ready' : experience.readiness.state === 'amber' ? 'Action required' : 'Blocked'}
                            </div>
                        </div>
                        <div className="mt-4 h-2 overflow-hidden rounded-full bg-muted">
                            <div className="h-full rounded-full bg-foreground transition-all" style={{ width: `${experience.readiness.percentage}%` }} />
                        </div>
                        {experience.readiness.blocking_items.length > 0 && (
                            <div className="mt-4 rounded-xl border border-destructive/20 bg-destructive/5 p-3">
                                <div className="flex items-center gap-2 text-sm font-medium"><CircleAlert className="h-4 w-4" /> Blocking items</div>
                                <p className="mt-1 text-sm text-muted-foreground">{experience.readiness.blocking_items.join(' · ')}</p>
                            </div>
                        )}
                    </section>

                    <section className="rounded-2xl border bg-card p-5">
                        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">Journey progress</p>
                        <div className="mt-4 space-y-3">
                            {experience.onboarding.steps.map((step) => (
                                <div key={step.key} className="flex items-center gap-3">
                                    {step.complete ? <CheckCircle2 className="h-4 w-4" /> : <CircleAlert className="h-4 w-4 text-muted-foreground" />}
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-medium">{step.label}</p>
                                        <p className="text-xs text-muted-foreground">{step.complete ? 'Complete' : step.blocking ? 'Required before operational release' : 'Recommended'}</p>
                                    </div>
                                    {!step.complete && step.action && <Link className="text-xs font-semibold underline-offset-4 hover:underline" href={step.action}>Open</Link>}
                                </div>
                            ))}
                        </div>
                    </section>
                </div>

                {experience.workspace.type === 'operator' && (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {phaseItems.map((item) => (
                            <MetricCard key={item.title} title={item.title} value={item.value} status={item.status} icon={item.icon} />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
