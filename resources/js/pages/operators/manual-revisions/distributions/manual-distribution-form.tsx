import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { ManualDistributionOptions, OperationsManualRevision } from '../../types';

type DistributionFormData = {
    recipient_name: string;
    recipient_role: string;
    recipient_email: string;
    distribution_channel: string;
    distribution_status: string;
    required_by: string;
    distributed_at: string;
    evidence_references: string[];
    notes: string;
};

export default function ManualDistributionForm({ manualRevision, options }: { manualRevision: OperationsManualRevision; options: ManualDistributionOptions }) {
    const { data, setData, post, processing, errors, transform } = useForm<DistributionFormData>({
        recipient_name: '',
        recipient_role: '',
        recipient_email: '',
        distribution_channel: 'manual_register',
        distribution_status: 'required',
        required_by: '',
        distributed_at: '',
        evidence_references: [],
        notes: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            recipient_email: payload.recipient_email || null,
            required_by: payload.required_by || null,
            distributed_at: payload.distributed_at || null,
            notes: payload.notes || null,
        }));

        post(`/operations-manual-revisions/${manualRevision.id}/distributions`);
    }

    return (
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Recipient name" error={errors.recipient_name}><Input value={data.recipient_name} onChange={(event) => setData('recipient_name', event.target.value)} /></Field>
                <Field label="Recipient role" error={errors.recipient_role}><Input value={data.recipient_role} onChange={(event) => setData('recipient_role', event.target.value)} placeholder="Accountable Manager" /></Field>
                <Field label="Email" error={errors.recipient_email}><Input type="email" value={data.recipient_email} onChange={(event) => setData('recipient_email', event.target.value)} /></Field>
                <Field label="Channel" error={errors.distribution_channel}><OptionSelect value={data.distribution_channel} options={options.channels} onChange={(value) => setData('distribution_channel', value)} /></Field>
                <Field label="Status" error={errors.distribution_status}><OptionSelect value={data.distribution_status} options={options.statuses} onChange={(value) => setData('distribution_status', value)} /></Field>
                <Field label="Required by" error={errors.required_by}><Input type="date" value={data.required_by} onChange={(event) => setData('required_by', event.target.value)} /></Field>
                <Field label="Distributed at" error={errors.distributed_at}><Input type="datetime-local" value={data.distributed_at} onChange={(event) => setData('distributed_at', event.target.value)} /></Field>
            </section>

            <section className="grid gap-5 md:grid-cols-2">
                <TextList label="Evidence references" value={data.evidence_references} onChange={(value) => setData('evidence_references', value)} error={errors.evidence_references} />
                <Field label="Notes" error={errors.notes}><textarea value={data.notes} onChange={(event) => setData('notes', event.target.value)} className="min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" /></Field>
            </section>

            <div className="flex flex-wrap items-center gap-3">
                <Button disabled={processing}><Save />Save recipient</Button>
                <Button variant="outline" asChild><Link href={`/operations-manual-revisions/${manualRevision.id}`}>Cancel</Link></Button>
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
