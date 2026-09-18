import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { OperatorCertificateCase } from '../types';

export default function Show({ certificateCase }: { certificateCase: OperatorCertificateCase }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Operators', href: '/operators' }, { title: certificateCase.operator.legal_entity, href: `/operators/${certificateCase.operator.id}` }, { title: certificateCase.case_number, href: `/operator-certificate-cases/${certificateCase.id}` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={certificateCase.case_number} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title={certificateCase.case_number} description={`${certificateCase.operator.legal_entity} / ${certificateCase.case_type.replaceAll('_', ' ')}`} actions={<div className="flex gap-2"><Button asChild variant="outline"><Link href={`/operator-certificate-cases/${certificateCase.id}/application-pack`}>Pack</Link></Button><Button asChild><Link href={`/operator-certificate-cases/${certificateCase.id}/edit`}>Edit</Link></Button></div>} />
                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Lifecycle"><div className="mb-3 flex gap-2"><StatusBadge value={certificateCase.status.replaceAll('_', ' ')} /><StatusBadge value={certificateCase.submission_status.replaceAll('_', ' ')} /></div><Detail label="Deadline" value={certificateCase.deadline_at} /><Detail label="Manual revision" value={certificateCase.operations_manual_revision} /><Detail label="Submitted" value={certificateCase.submitted_at} /><Detail label="Decided" value={certificateCase.decided_at} /></Panel>
                    <Panel title="Scope"><List label="Fleet" values={certificateCase.fleet_scope} /><List label="Personnel" values={certificateCase.personnel_scope} /><List label="OpsSpec" values={certificateCase.ops_spec_scope} /><List label="Fees" values={certificateCase.fees} /></Panel>
                    <Panel title="Evidence"><List label="Requirements" values={certificateCase.evidence_requirements} /><List label="Outstanding documents" values={certificateCase.outstanding_documents} /><List label="Authority correspondence" values={certificateCase.authority_correspondence} /><Detail label="Outcome" value={certificateCase.outcome} /></Panel>
                    <Panel title="Regulatory Traceability"><Detail label="Source" value={certificateCase.regulatory_source} /><Detail label="Version" value={certificateCase.regulatory_source_version} /><Detail label="Effective date" value={certificateCase.regulatory_effective_date} /></Panel>
                </div>
            </div>
        </AppLayout>
    );
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) {
    return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><h2 className="mb-4 text-base font-semibold">{title}</h2><div className="space-y-3">{children}</div></section>;
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{value || 'Not captured'}</dd></div>;
}

function List({ label, values }: { label: string; values: string[] }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{values.length ? values.join(', ') : 'Not captured'}</dd></div>;
}
