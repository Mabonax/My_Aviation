import { MetricCard } from '@/components/uas/metric-card';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { AlertTriangle, ClipboardCheck, FileWarning, Gauge, Wrench } from 'lucide-react';

interface ComplianceDomain {
    key: string;
    label: string;
    score: number;
    status: string;
    open_findings: number;
    critical_findings: number;
    warning_findings: number;
    info_findings: number;
}

interface CriticalFinding {
    id: number;
    requirement_id: string;
    state: string;
    severity: string;
    summary: string;
    recommended_action: string | null;
    due_at: string | null;
}

interface ComplianceRegisterReport {
    generated_at: string;
    overall_score: number;
    critical_findings_count: number;
    open_findings_count: number;
    expiring_within_30_days: number;
    open_corrective_actions: number;
    domains: ComplianceDomain[];
    critical_findings: CriticalFinding[];
}

export default function Register({ report }: { report: ComplianceRegisterReport }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Compliance', href: '/compliance/register' }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Compliance register" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Compliance register" description={`Generated ${new Date(report.generated_at).toLocaleString()}`} />
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <MetricCard title="Organisation compliance" value={`${report.overall_score}%`} status={report.critical_findings_count > 0 ? 'Critical' : 'Current'} icon={Gauge} />
            <MetricCard title="Critical findings" value={report.critical_findings_count.toString()} status={report.critical_findings_count > 0 ? 'Visible' : 'Clear'} icon={AlertTriangle} />
            <MetricCard title="Open findings" value={report.open_findings_count.toString()} status="Tracked" icon={FileWarning} />
            <MetricCard title="Corrective actions" value={report.open_corrective_actions.toString()} status="Open" icon={Wrench} />
        </div>

        {report.critical_findings.length > 0 && <section className="rounded-lg border border-destructive/30 bg-card text-card-foreground shadow-xs">
            <div className="border-b px-4 py-3">
                <h2 className="text-base font-semibold text-destructive">Critical Findings</h2>
            </div>
            <div className="divide-y">
                {report.critical_findings.map((finding) => <div key={finding.id} className="px-4 py-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="font-medium">{finding.requirement_id}</p>
                        <StatusBadge value={finding.severity} />
                    </div>
                    <p className="mt-1 text-sm">{finding.summary}</p>
                    <p className="mt-2 text-xs text-muted-foreground">{finding.recommended_action || 'No corrective action captured'}{finding.due_at ? ` / due ${finding.due_at}` : ''}</p>
                </div>)}
            </div>
        </section>}

        <section className="overflow-hidden rounded-lg border bg-card text-card-foreground shadow-xs">
            <table className="w-full text-left text-sm">
                <thead className="border-b bg-muted/40 text-muted-foreground">
                    <tr>
                        <th className="p-3 font-medium">Domain</th>
                        <th className="p-3 font-medium">Score</th>
                        <th className="p-3 font-medium">Status</th>
                        <th className="p-3 font-medium">Findings</th>
                    </tr>
                </thead>
                <tbody>
                    {report.domains.map((domain) => <tr key={domain.key} className="border-b last:border-b-0">
                        <td className="p-3 font-medium">{domain.label}</td>
                        <td className="p-3">{domain.score}%</td>
                        <td className="p-3"><StatusBadge value={domain.status} /></td>
                        <td className="p-3 text-muted-foreground">{domain.open_findings} open / {domain.critical_findings} critical / {domain.warning_findings} warning</td>
                    </tr>)}
                </tbody>
            </table>
        </section>

        {report.domains.length === 0 && <div className="flex min-h-48 items-center justify-center rounded-lg border text-sm text-muted-foreground"><ClipboardCheck className="mr-2 size-4" />No compliance domains configured.</div>}
    </div></AppLayout>;
}
