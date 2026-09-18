import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { ExternalIntegrationOptions } from './types';

type IntegrationFormData = {
    name: string;
    authority: string;
    classification: string;
    regulatory_area: string;
    supported_process: string;
    authoritative_url: string;
    evidence_required: string;
    workflow_notes: string;
    api_assumption_blocked: boolean;
    status: string;
    verified_at: string;
};

export default function Create({ options }: { options: ExternalIntegrationOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'External integrations', href: '/regulatory-external-integrations' }, { title: 'New', href: '/regulatory-external-integrations/create' }];
    const { data, setData, post, processing, errors, transform } = useForm<IntegrationFormData>({
        name: '',
        authority: 'SACAA',
        classification: 'manual',
        regulatory_area: 'Part 101',
        supported_process: '',
        authoritative_url: '',
        evidence_required: '',
        workflow_notes: '',
        api_assumption_blocked: true,
        status: 'active',
        verified_at: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({ ...payload, authoritative_url: payload.authoritative_url || null, verified_at: payload.verified_at || null }));
        post('/regulatory-external-integrations');
    }

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="New external regulatory integration" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="New external regulatory integration" description="Classify external authority processes without assuming undocumented SACAA APIs." />
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Name" error={errors.name}><Input value={data.name} onChange={(event) => setData('name', event.target.value)} /></Field>
                <Field label="Authority" error={errors.authority}><Input value={data.authority} onChange={(event) => setData('authority', event.target.value)} /></Field>
                <Field label="Classification" error={errors.classification}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.classification} onChange={(event) => setData('classification', event.target.value)}>{Object.entries(options.classifications).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                <Field label="Regulatory area" error={errors.regulatory_area}><Input value={data.regulatory_area} onChange={(event) => setData('regulatory_area', event.target.value)} /></Field>
                <Field label="Supported process" error={errors.supported_process}><Input value={data.supported_process} onChange={(event) => setData('supported_process', event.target.value)} /></Field>
                <Field label="Status" error={errors.status}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.status} onChange={(event) => setData('status', event.target.value)}>{Object.entries(options.statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                <Field label="Authoritative URL" error={errors.authoritative_url}><Input value={data.authoritative_url} onChange={(event) => setData('authoritative_url', event.target.value)} /></Field>
                <Field label="Evidence required" error={errors.evidence_required}><Input value={data.evidence_required} onChange={(event) => setData('evidence_required', event.target.value)} /></Field>
                <Field label="Verified at" error={errors.verified_at}><Input type="datetime-local" value={data.verified_at} onChange={(event) => setData('verified_at', event.target.value)} /></Field>
            </section>
            <Field label="Workflow notes" error={errors.workflow_notes}><textarea className="min-h-32 w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.workflow_notes} onChange={(event) => setData('workflow_notes', event.target.value)} /></Field>
            <div className="flex items-center gap-3 rounded-lg border p-4"><Checkbox checked={data.api_assumption_blocked} onCheckedChange={(checked) => setData('api_assumption_blocked', checked === true)} /><Label>Block undocumented API assumptions</Label><InputError message={errors.api_assumption_blocked} /></div>
            <Button disabled={processing}><Save />Save integration</Button>
        </form>
    </div></AppLayout>;
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) { return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>; }
