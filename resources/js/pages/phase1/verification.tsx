import { Button } from '@/components/ui/button';
import { MetricCard } from '@/components/uas/metric-card';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Bell, Download, FileWarning, ListChecks, Plane, UserRound } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Phase 1 verification', href: '/phase-1/verification' },
];

interface VerificationRequirement {
    code: string;
    name: string;
    status: string;
    evidence: string;
}

interface Finding {
    id: number;
    requirement_id: string;
    state: string;
    severity: string;
    summary: string;
    due_at: string | null;
}

interface PendingNotification {
    id: number;
    requirement_id: string | null;
    notification_type: string;
    subject: string;
    due_at: string | null;
}

interface VerificationReport {
    generated_at: string;
    counts: Record<string, number>;
    requirements: VerificationRequirement[];
    open_findings: Finding[];
    pending_notifications: PendingNotification[];
}

export default function Verification({ report }: { report: VerificationReport }) {
    const counts = report.counts;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Phase 1 verification" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Phase 1 verification"
                    description="Authenticated compliance evidence, export and notification planning surface for the Phase 1 release."
                    actions={
                        <Button asChild>
                            <a href="/phase-1/verification/export">
                                Export CSV
                                <Download />
                            </a>
                        </Button>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard title="Pilot records" value={(counts.pilots ?? 0).toString()} status="Evidence" icon={UserRound} />
                    <MetricCard title="Aircraft records" value={(counts.aircraft ?? 0).toString()} status="Evidence" icon={Plane} />
                    <MetricCard title="Open findings" value={(counts.compliance_findings ?? 0).toString()} status="Control" icon={FileWarning} />
                    <MetricCard title="Notifications" value={(counts.compliance_notifications ?? 0).toString()} status="Planned" icon={Bell} />
                </div>

                <section className="rounded-lg border bg-card text-card-foreground shadow-xs">
                    <div className="flex items-center gap-3 border-b px-4 py-3">
                        <ListChecks className="size-5 text-primary" />
                        <div>
                            <h2 className="text-base font-semibold">Traceability Matrix</h2>
                            <p className="text-sm text-muted-foreground">Generated {new Date(report.generated_at).toLocaleString()}</p>
                        </div>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-3xl text-left text-sm">
                            <thead className="border-b bg-muted/40 text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Requirement</th>
                                    <th className="px-4 py-3 font-medium">Control</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium">Evidence</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.requirements.map((requirement) => (
                                    <tr key={requirement.code} className="border-b last:border-b-0">
                                        <td className="px-4 py-3 font-medium">{requirement.code}</td>
                                        <td className="px-4 py-3">{requirement.name}</td>
                                        <td className="px-4 py-3"><StatusBadge value={requirement.status.replaceAll('_', ' ')} /></td>
                                        <td className="px-4 py-3 text-muted-foreground">{requirement.evidence}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <div className="grid gap-4 xl:grid-cols-2">
                    <EvidenceList title="Open Findings" rows={report.open_findings.map((finding) => ({
                        id: finding.id,
                        title: `${finding.requirement_id} - ${finding.severity}`,
                        body: finding.summary,
                    }))} />
                    <EvidenceList title="Pending Notifications" rows={report.pending_notifications.map((notification) => ({
                        id: notification.id,
                        title: notification.notification_type,
                        body: notification.subject,
                    }))} />
                </div>

                <div>
                    <Button variant="outline" asChild>
                        <Link href="/dashboard">Back to dashboard</Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}

function EvidenceList({ title, rows }: { title: string; rows: Array<{ id: number; title: string; body: string }> }) {
    return (
        <section className="rounded-lg border bg-card text-card-foreground shadow-xs">
            <h2 className="border-b px-4 py-3 text-base font-semibold">{title}</h2>
            <div className="divide-y">
                {rows.length ? rows.map((row) => (
                    <div key={row.id} className="px-4 py-3">
                        <div className="font-medium">{row.title}</div>
                        <div className="text-sm text-muted-foreground">{row.body}</div>
                    </div>
                )) : (
                    <div className="px-4 py-6 text-sm text-muted-foreground">No records require attention.</div>
                )}
            </div>
        </section>
    );
}
