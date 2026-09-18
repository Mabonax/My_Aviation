import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { ManualRevisionOptions, OperationsManualRevision, OperatorProfile } from '../types';

type ManualRevisionFormData = {
    manual_name: string;
    revision_code: string;
    effective_date: string;
    approval_status: string;
    authority_approval_reference: string;
    superseded_revision_id: string;
    sections: string[];
    change_summary: string;
    evidence_references: string[];
};

export default function ManualRevisionForm({ operator, manualRevision, options }: { operator?: OperatorProfile; manualRevision?: OperationsManualRevision; options: ManualRevisionOptions }) {
    const operatorId = operator?.id ?? manualRevision?.operator.id;
    const { data, setData, post, put, processing, errors, transform } = useForm<ManualRevisionFormData>({
        manual_name: manualRevision?.manual_name ?? 'Operations Manual',
        revision_code: manualRevision?.revision_code ?? '',
        effective_date: manualRevision?.effective_date ?? '',
        approval_status: manualRevision?.approval_status ?? 'draft',
        authority_approval_reference: manualRevision?.authority_approval_reference ?? '',
        superseded_revision_id: manualRevision?.superseded_revision_id ? manualRevision.superseded_revision_id.toString() : 'none',
        sections: manualRevision?.sections ?? [],
        change_summary: manualRevision?.change_summary ?? '',
        evidence_references: manualRevision?.evidence_references ?? [],
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            effective_date: payload.effective_date || null,
            authority_approval_reference: payload.authority_approval_reference || null,
            superseded_revision_id: payload.superseded_revision_id === 'none' ? null : Number(payload.superseded_revision_id),
            change_summary: payload.change_summary || null,
        }));

        if (manualRevision) {
            put(`/operations-manual-revisions/${manualRevision.id}`);
            return;
        }

        post(`/operators/${operatorId}/manual-revisions`);
    }

    return (
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Manual name" error={errors.manual_name}><Input value={data.manual_name} onChange={(event) => setData('manual_name', event.target.value)} /></Field>
                <Field label="Revision code" error={errors.revision_code}><Input value={data.revision_code} onChange={(event) => setData('revision_code', event.target.value)} placeholder="OM-REV-001" /></Field>
                <Field label="Effective date" error={errors.effective_date}><Input type="date" value={data.effective_date} onChange={(event) => setData('effective_date', event.target.value)} /></Field>
                <Field label="Approval status" error={errors.approval_status}><OptionSelect value={data.approval_status} options={options.approval_statuses} onChange={(value) => setData('approval_status', value)} /></Field>
                <Field label="Authority approval reference" error={errors.authority_approval_reference}><Input value={data.authority_approval_reference} onChange={(event) => setData('authority_approval_reference', event.target.value)} /></Field>
                <Field label="Superseded revision" error={errors.superseded_revision_id}>
                    <Select value={data.superseded_revision_id} onValueChange={(value) => setData('superseded_revision_id', value)}>
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">None</SelectItem>
                            {options.superseded_revisions.filter((revision) => revision.id !== manualRevision?.id).map((revision) => <SelectItem key={revision.id} value={revision.id.toString()}>{revision.label}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </Field>
            </section>

            <section className="grid gap-5 md:grid-cols-2">
                <TextList label="Sections" value={data.sections} onChange={(value) => setData('sections', value)} error={errors.sections} />
                <TextList label="Evidence references" value={data.evidence_references} onChange={(value) => setData('evidence_references', value)} error={errors.evidence_references} />
                <Field label="Change summary" error={errors.change_summary}><textarea value={data.change_summary} onChange={(event) => setData('change_summary', event.target.value)} className="min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" /></Field>
            </section>

            <div className="flex flex-wrap items-center gap-3">
                <Button disabled={processing}><Save />Save revision</Button>
                <Button variant="outline" asChild><Link href={manualRevision ? `/operations-manual-revisions/${manualRevision.id}` : `/operators/${operatorId}`}>Cancel</Link></Button>
            </div>
        </form>
    );
}

function lines(value: string) {
    return value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
}

function OptionSelect({ value, options, onChange }: { value: string; options: Record<string, string>; onChange: (value: string) => void }) {
    return <Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(options).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent></Select>;
}

function TextList({ label, value, onChange, error }: { label: string; value: string[]; onChange: (value: string[]) => void; error?: string }) {
    return <Field label={label} error={error}><textarea value={value.join('\n')} onChange={(event) => onChange(lines(event.target.value))} className="min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" /></Field>;
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>;
}
