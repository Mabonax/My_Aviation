import { MetricCard } from '@/components/uas/metric-card';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { AlertTriangle, FileSearch, Link2, Network } from 'lucide-react';

interface TraceabilityControl {
    id: number;
    control_type: string;
    requirement_id: string | null;
    control: string;
    state: string;
    traceability_status: string;
    regulatory_source: string | null;
    source_version: string | null;
    responsible_party: string | null;
    applicability: string | null;
    evidence_required: string | null;
    effective_date: string | null;
    requirement_status: string | null;
}

interface TraceabilityReport {
    generated_at: string;
    summary: {
        total_controls: number;
        traceable_controls: number;
        source_text_only_controls: number;
        missing_source_controls: number;
    };
    controls: TraceabilityControl[];
}

export default function Traceability({ report }: { report: TraceabilityReport }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Traceability', href: '/compliance/traceability' }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Compliance traceability" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Compliance traceability" description={`Generated ${new Date(report.generated_at).toLocaleString()}`} />
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <MetricCard title="Controls" value={report.summary.total_controls.toString()} status="Observed" icon={Network} />
            <MetricCard title="Traceable" value={report.summary.traceable_controls.toString()} status="Registered" icon={Link2} />
            <MetricCard title="Source text only" value={report.summary.source_text_only_controls.toString()} status="Review" icon={FileSearch} />
            <MetricCard title="Missing source" value={report.summary.missing_source_controls.toString()} status={report.summary.missing_source_controls > 0 ? 'Attention' : 'Valid'} icon={AlertTriangle} />
        </div>
        <section className="overflow-hidden rounded-lg border bg-card text-card-foreground shadow-xs">
            <table className="w-full text-left text-sm">
                <thead className="border-b bg-muted/40 text-muted-foreground">
                    <tr>
                        <th className="p-3 font-medium">Control</th>
                        <th className="p-3 font-medium">Requirement</th>
                        <th className="p-3 font-medium">Traceability</th>
                        <th className="p-3 font-medium">Source</th>
                    </tr>
                </thead>
                <tbody>
                    {report.controls.map((control) => <tr key={`${control.control_type}-${control.id}`} className="border-b last:border-b-0">
                        <td className="p-3"><p className="font-medium capitalize">{control.control_type}</p><p className="mt-1 text-muted-foreground">{control.control}</p></td>
                        <td className="p-3"><p>{control.requirement_id || 'Not captured'}</p><p className="mt-1 text-xs text-muted-foreground">{control.responsible_party || 'Responsible party not linked'}</p></td>
                        <td className="p-3"><StatusBadge value={control.traceability_status.replaceAll('_', ' ')} /><p className="mt-1 text-xs text-muted-foreground">{control.state}</p></td>
                        <td className="p-3 text-muted-foreground">{control.regulatory_source || 'No source captured'}{control.source_version ? ` / ${control.source_version}` : ''}</td>
                    </tr>)}
                </tbody>
            </table>
        </section>
    </div></AppLayout>;
}
