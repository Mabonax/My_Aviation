import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { RegulatoryRequirement } from './types';

export default function Show({ requirement }: { requirement: RegulatoryRequirement }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Regulations', href: '/regulatory-requirements' }, { title: requirement.requirement_id, href: `/regulatory-requirements/${requirement.id}` }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title={requirement.requirement_id} /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title={`${requirement.requirement_id} / ${requirement.title}`} description={`${requirement.regulation_part}${requirement.clause_reference ? ` / ${requirement.clause_reference}` : ''}`} actions={<Button asChild><Link href={`/regulatory-requirements/${requirement.id}/supersede`}>Create Version</Link></Button>} />
        <div className="grid gap-4 xl:grid-cols-2">
            <Panel title="Source"><div className="mb-3"><StatusBadge value={requirement.status} /></div><Detail label="Official source" value={requirement.official_source} /><Detail label="Source version" value={requirement.source_version} /><Detail label="Effective date" value={requirement.effective_date} /><Detail label="Superseded date" value={requirement.superseded_date} /></Panel>
            <Panel title="Requirement"><Detail label="Responsible party" value={requirement.responsible_party} /><Detail label="Text" value={requirement.requirement_text} /><Detail label="Applicability" value={requirement.applicability} /></Panel>
            <Panel title="Control"><Detail label="System control" value={requirement.system_control} /><Detail label="Evidence required" value={requirement.evidence_required} /><Detail label="Frequency" value={requirement.frequency} /><Detail label="Validity period" value={requirement.validity_period} /><Detail label="Retention period" value={requirement.retention_period} /></Panel>
            <Panel title="Version History"><Detail label="Previous version" value={requirement.previous_requirement ? `${requirement.previous_requirement.requirement_id} / ${requirement.previous_requirement.source_version}` : null} />{requirement.superseding_requirements.length === 0 ? <p className="text-sm text-muted-foreground">No superseding version captured.</p> : <List values={requirement.superseding_requirements.map((version) => `${version.requirement_id} / ${version.source_version} / ${version.status}`)} />}</Panel>
            <Panel title="Training Links">{requirement.training_links.length === 0 ? <p className="text-sm text-muted-foreground">No linked training controls.</p> : <List values={requirement.training_links.map((link) => `${link.course || 'Course not available'} / ${link.link_status}`)} />}</Panel>
        </div></div></AppLayout>;
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) { return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><h2 className="mb-4 text-base font-semibold">{title}</h2><div className="space-y-3">{children}</div></section>; }
function Detail({ label, value }: { label: string; value?: string | null }) { return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{value || 'Not captured'}</dd></div>; }
function List({ values }: { values: string[] }) { return <ul className="space-y-2 text-sm">{values.map((value) => <li key={value} className="rounded-md border px-3 py-2">{value}</li>)}</ul>; }
