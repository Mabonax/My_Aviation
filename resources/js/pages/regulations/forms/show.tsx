import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { RegulatoryForm } from './types';

export default function Show({ form }: { form: RegulatoryForm }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Forms', href: '/regulatory-forms' }, { title: form.form_code, href: `/regulatory-forms/${form.id}` }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title={form.form_code} /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title={`${form.form_code} / ${form.form_title}`} description={`${form.regulatory_area} / ${form.required_transaction}`} actions={<Button asChild><Link href={`/regulatory-forms/${form.id}/supersede`}>Create Version</Link></Button>} />
        <div className="grid gap-4 xl:grid-cols-2">
            <Panel title="Source"><div className="mb-3"><StatusBadge value={form.status} /></div><Detail label="Revision" value={form.revision} /><Detail label="Effective date" value={form.effective_date} /><Detail label="Superseded date" value={form.superseded_date} /><Detail label="Verified at" value={form.verified_at} /></Panel>
            <Panel title="Authority Reference"><Detail label="Source reference" value={form.source_reference} /><Detail label="Source URL" value={form.source_url} /><Detail label="Required transaction" value={form.required_transaction} /></Panel>
            <Panel title="Version History"><Detail label="Previous version" value={form.previous_form ? `${form.previous_form.form_code} / ${form.previous_form.revision}` : null} />{form.superseding_forms.length === 0 ? <p className="text-sm text-muted-foreground">No superseding form revision captured.</p> : <List values={form.superseding_forms.map((version) => `${version.form_code} / ${version.revision} / ${version.status}`)} />}</Panel>
        </div></div></AppLayout>;
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) { return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><h2 className="mb-4 text-base font-semibold">{title}</h2><div className="space-y-3">{children}</div></section>; }
function Detail({ label, value }: { label: string; value?: string | null }) { return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{value || 'Not captured'}</dd></div>; }
function List({ values }: { values: string[] }) { return <ul className="space-y-2 text-sm">{values.map((value) => <li key={value} className="rounded-md border px-3 py-2">{value}</li>)}</ul>; }
