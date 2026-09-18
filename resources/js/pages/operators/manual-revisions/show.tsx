import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ManualDistributionReport, ManualTrainingReport, OperationsManualRevision } from '../types';

export default function Show({ manualRevision, distributionReport, trainingReport }: { manualRevision: OperationsManualRevision; distributionReport: ManualDistributionReport; trainingReport: ManualTrainingReport }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Operators', href: '/operators' },
        { title: manualRevision.operator.legal_entity, href: `/operators/${manualRevision.operator.id}` },
        { title: manualRevision.revision_code, href: `/operations-manual-revisions/${manualRevision.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={manualRevision.revision_code} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title={`${manualRevision.manual_name} ${manualRevision.revision_code}`} description={manualRevision.operator.legal_entity} actions={<Button asChild><Link href={`/operations-manual-revisions/${manualRevision.id}/edit`}>Edit</Link></Button>} />
                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Revision Control"><div className="mb-3"><StatusBadge value={manualRevision.approval_status.replaceAll('_', ' ')} /></div><Detail label="Effective date" value={manualRevision.effective_date} /><Detail label="Authority approval reference" value={manualRevision.authority_approval_reference} /><Detail label="Supersedes" value={manualRevision.superseded_revision?.label} /></Panel>
                    <Panel title="Distribution" action={<Button size="sm" asChild><Link href={`/operations-manual-revisions/${manualRevision.id}/distributions/create`}>Add Recipient</Link></Button>}>
                        <div className="grid gap-2 text-sm sm:grid-cols-5">
                            <Detail label="Total" value={distributionReport.summary.total.toString()} />
                            <Detail label="Required" value={distributionReport.summary.required.toString()} />
                            <Detail label="Distributed" value={distributionReport.summary.distributed.toString()} />
                            <Detail label="Waived" value={distributionReport.summary.waived.toString()} />
                            <Detail label="Acknowledged" value={distributionReport.summary.acknowledged.toString()} />
                        </div>
                        {distributionReport.distributions.length === 0 ? <p className="text-sm text-muted-foreground">No required recipients have been recorded for this revision.</p> : distributionReport.distributions.map((recipient) => (
                            <div key={recipient.id} className="border-t py-3 first:border-t-0 first:pt-0">
                                <div className="flex items-center justify-between gap-3"><div><p className="font-medium">{recipient.recipient_name}</p><p className="text-sm text-muted-foreground">{recipient.recipient_role}{recipient.recipient_email ? ` / ${recipient.recipient_email}` : ''}</p></div><div className="flex items-center gap-2"><StatusBadge value={recipient.distribution_status.replaceAll('_', ' ')} /><StatusBadge value={recipient.acknowledgement_status.replaceAll('_', ' ')} /></div></div>
                                <p className="mt-1 text-sm text-muted-foreground">{recipient.distribution_channel.replaceAll('_', ' ')} / required by {recipient.required_by || 'not set'} / acknowledged {recipient.acknowledged_at || 'not recorded'}</p>
                                {recipient.acknowledgement_status !== 'acknowledged' ? <Button size="sm" variant="outline" className="mt-3" asChild><Link href={`/operations-manual-distributions/${recipient.id}/acknowledge`}>Acknowledge</Link></Button> : null}
                            </div>
                        ))}
                    </Panel>
                    <Panel title="Training Triggers" action={<Button size="sm" asChild><Link href={`/operations-manual-revisions/${manualRevision.id}/training-requirements/create`}>Add Requirement</Link></Button>}>
                        <div className="grid gap-2 text-sm sm:grid-cols-5"><Detail label="Total" value={trainingReport.summary.total.toString()} /><Detail label="Required" value={trainingReport.summary.required.toString()} /><Detail label="Assigned" value={trainingReport.summary.assigned.toString()} /><Detail label="Completed" value={trainingReport.summary.completed.toString()} /><Detail label="Waived" value={trainingReport.summary.waived.toString()} /></div>
                        {trainingReport.requirements.length === 0 ? <p className="text-sm text-muted-foreground">No training or competency requirements have been triggered by this amendment.</p> : trainingReport.requirements.map((requirement) => (
                            <div key={requirement.id} className="border-t py-3 first:border-t-0 first:pt-0">
                                <div className="flex items-center justify-between gap-3"><p className="font-medium">{requirement.title}</p><StatusBadge value={requirement.training_status.replaceAll('_', ' ')} /></div>
                                <p className="mt-1 text-sm text-muted-foreground">{requirement.requirement_type.replaceAll('_', ' ')} / due {requirement.due_date || 'not set'} / {requirement.affected_roles.length ? requirement.affected_roles.join(', ') : 'roles not specified'}</p>
                            </div>
                        ))}
                    </Panel>
                    <Panel title="Controlled Sections"><List values={manualRevision.sections} /></Panel>
                    <Panel title="Change Summary"><p className="text-sm leading-6">{manualRevision.change_summary || 'Not captured'}</p></Panel>
                    <Panel title="Evidence References"><List values={manualRevision.evidence_references} /></Panel>
                    <Panel title="Regulatory Traceability"><Detail label="Source" value={manualRevision.regulatory_source} /><Detail label="Version" value={manualRevision.regulatory_source_version} /><Detail label="Effective date" value={manualRevision.regulatory_effective_date} /></Panel>
                </div>
            </div>
        </AppLayout>
    );
}

function Panel({ title, action, children }: { title: string; action?: React.ReactNode; children: React.ReactNode }) {
    return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><div className="mb-4 flex items-center justify-between gap-3"><h2 className="text-base font-semibold">{title}</h2>{action}</div><div className="space-y-3">{children}</div></section>;
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{value || 'Not captured'}</dd></div>;
}

function List({ values }: { values: string[] }) {
    return values.length ? <ul className="space-y-2 text-sm">{values.map((value) => <li key={value} className="rounded-md border px-3 py-2">{value}</li>)}</ul> : <p className="text-sm text-muted-foreground">Not captured</p>;
}
