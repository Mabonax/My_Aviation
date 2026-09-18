import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { RegulatoryFee } from './types';

export default function Show({ fee }: { fee: RegulatoryFee }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Fees', href: '/regulatory-fees' }, { title: fee.transaction_code, href: `/regulatory-fees/${fee.id}` }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title={fee.transaction_code} /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title={`${fee.transaction_code} / ${fee.description}`} description={`${fee.regulation_part} / ${fee.source_version}`} actions={<Button asChild><Link href={`/regulatory-fees/${fee.id}/supersede`}>Create Version</Link></Button>} />
        <div className="grid gap-4 xl:grid-cols-2">
            <Panel title="Tariff"><div className="mb-3"><StatusBadge value={fee.status} /></div><Detail label="Amount" value={`${fee.amount ?? 'TBC'} ${fee.currency}`} /><Detail label="Effective from" value={fee.effective_from} /><Detail label="Effective to" value={fee.effective_to} /><Detail label="Verified at" value={fee.verified_at} /></Panel>
            <Panel title="Source"><Detail label="Source" value={fee.source} /><Detail label="Source version" value={fee.source_version} /><Detail label="Transaction code" value={fee.transaction_code} /></Panel>
            <Panel title="Version History"><Detail label="Previous version" value={fee.previous_fee ? `${fee.previous_fee.transaction_code} / ${fee.previous_fee.source_version}` : null} />{fee.superseding_fees.length === 0 ? <p className="text-sm text-muted-foreground">No newer tariff captured.</p> : <List values={fee.superseding_fees.map((version) => `${version.transaction_code} / ${version.source_version} / ${version.amount ?? 'TBC'} ${version.currency}`)} />}</Panel>
        </div></div></AppLayout>;
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) { return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><h2 className="mb-4 text-base font-semibold">{title}</h2><div className="space-y-3">{children}</div></section>; }
function Detail({ label, value }: { label: string; value?: string | null }) { return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{value || 'Not captured'}</dd></div>; }
function List({ values }: { values: string[] }) { return <ul className="space-y-2 text-sm">{values.map((value) => <li key={value} className="rounded-md border px-3 py-2">{value}</li>)}</ul>; }
