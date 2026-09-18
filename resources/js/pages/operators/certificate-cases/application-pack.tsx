import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ApplicationRenewalPack } from '../types';

export default function ApplicationPack({ pack }: { pack: ApplicationRenewalPack }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Operators', href: '/operators' },
        { title: pack.case.operator.legal_entity, href: `/operators/${pack.case.operator.id}` },
        { title: pack.case.case_number, href: `/operator-certificate-cases/${pack.case.id}` },
        { title: 'Application Pack', href: `/operator-certificate-cases/${pack.case.id}/application-pack` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${pack.case.case_number} application pack`} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title={pack.cover_sheet.title} description={`${pack.cover_sheet.operator} / readiness ${pack.readiness.score}%`} />
                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Cover Sheet"><Detail label="Case" value={pack.cover_sheet.case_number} /><Detail label="Deadline" value={pack.cover_sheet.deadline_at} /><Detail label="Source" value={pack.cover_sheet.regulatory_source} /><Detail label="Version" value={pack.cover_sheet.regulatory_source_version} /></Panel>
                    <Panel title="Readiness"><div className="text-4xl font-semibold">{pack.readiness.score}%</div><p className="text-sm text-muted-foreground">{pack.readiness.complete} of {pack.readiness.total} pack checks complete</p><div className="mt-4 space-y-2">{pack.checklist.map((item) => <div key={`${item.category}-${item.label}`} className="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm"><span>{item.label}</span><StatusBadge value={item.complete ? 'complete' : 'open'} /></div>)}</div></Panel>
                    <Panel title="Required Forms">{pack.required_forms.length === 0 ? <p className="text-sm text-muted-foreground">No current form mapped to this transaction.</p> : <ul className="space-y-2 text-sm">{pack.required_forms.map((form) => <li key={form.id} className="rounded-md border px-3 py-2"><Link href={`/regulatory-forms/${form.id}`} className="font-medium hover:underline">{form.form_code} / {form.form_title}</Link><p className="mt-1 text-xs text-muted-foreground">{form.revision} / {form.required_transaction}</p></li>)}</ul>}</Panel>
                    <Panel title="Applicable Fees">{pack.applicable_fees.length === 0 ? <p className="text-sm text-muted-foreground">No current fee mapped to this transaction.</p> : <ul className="space-y-2 text-sm">{pack.applicable_fees.map((fee) => <li key={fee.id} className="rounded-md border px-3 py-2"><Link href={`/regulatory-fees/${fee.id}`} className="font-medium hover:underline">{fee.transaction_code} / {fee.description}</Link><p className="mt-1 text-xs text-muted-foreground">{fee.amount ?? 'TBC'} {fee.currency} / {fee.source_version}</p></li>)}</ul>}</Panel>
                    <Panel title="Evidence Index">{pack.evidence_index.map((group) => <div key={group.category}><dt className="text-xs font-medium uppercase text-muted-foreground">{group.category}</dt><dd className="mt-1 text-sm">{group.items.length ? group.items.join(', ') : 'Not captured'}</dd></div>)}</Panel>
                </div>
            </div>
        </AppLayout>
    );
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) { return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><h2 className="mb-4 text-base font-semibold">{title}</h2><div className="space-y-3">{children}</div></section>; }
function Detail({ label, value }: { label: string; value?: string | null }) { return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{value || 'Not captured'}</dd></div>; }
