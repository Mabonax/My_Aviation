import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent } from 'react';
import { ExternalIntegration, ExternalIntegrationOptions } from './types';

export default function Show({ integration, options }: { integration: ExternalIntegration; options: ExternalIntegrationOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'External integrations', href: '/regulatory-external-integrations' }, { title: integration.name, href: `/regulatory-external-integrations/${integration.id}` }];
    const { data, setData, put, processing, errors } = useForm({ status: integration.status });

    function submit(event: FormEvent) {
        event.preventDefault();
        put(`/regulatory-external-integrations/${integration.id}/status`);
    }

    return <AppLayout breadcrumbs={breadcrumbs}><Head title={integration.name} /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title={integration.name} description={`${integration.authority} / ${integration.supported_process}`} />
        <div className="grid gap-4 xl:grid-cols-2">
            <Panel title="Classification"><div className="mb-3"><StatusBadge value={integration.status} /></div><Detail label="Classification" value={integration.classification.replaceAll('_', ' ')} /><Detail label="Regulatory area" value={integration.regulatory_area} /><Detail label="API assumption" value={integration.api_assumption_blocked ? 'Blocked until documented authority API exists' : 'Allowed by documented integration decision'} /><Detail label="Verified at" value={integration.verified_at} /></Panel>
            <Panel title="Authority Process"><Detail label="Authority" value={integration.authority} /><Detail label="Supported process" value={integration.supported_process} /><Detail label="Authoritative URL" value={integration.authoritative_url} /><Detail label="Evidence required" value={integration.evidence_required} /></Panel>
            <Panel title="Workflow Notes"><p className="text-sm leading-6">{integration.workflow_notes}</p></Panel>
            <Panel title="Lifecycle"><form onSubmit={submit} className="space-y-4"><div><Label>Status</Label><select className="mt-2 w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.status} onChange={(event) => setData('status', event.target.value)}>{Object.entries(options.statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select>{errors.status && <p className="mt-2 text-sm text-destructive">{errors.status}</p>}</div><Button disabled={processing}><Save />Update status</Button></form></Panel>
        </div>
    </div></AppLayout>;
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) { return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><h2 className="mb-4 text-base font-semibold">{title}</h2><div className="space-y-3">{children}</div></section>; }
function Detail({ label, value }: { label: string; value?: string | null }) { return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm break-words">{value || 'Not captured'}</dd></div>; }
