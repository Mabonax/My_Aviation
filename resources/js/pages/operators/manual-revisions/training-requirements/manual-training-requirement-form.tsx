import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { ManualTrainingOptions, OperationsManualRevision } from '../../types';

type TrainingFormData = {
    title: string;
    requirement_type: string;
    training_status: string;
    affected_roles: string[];
    due_date: string;
    competency_standard: string;
    trigger_reason: string;
    evidence_references: string[];
    notes: string;
};

export default function ManualTrainingRequirementForm({ manualRevision, options }: { manualRevision: OperationsManualRevision; options: ManualTrainingOptions }) {
    const { data, setData, post, processing, errors, transform } = useForm<TrainingFormData>({
        title: '',
        requirement_type: 'operator_internal_competency',
        training_status: 'required',
        affected_roles: [],
        due_date: '',
        competency_standard: '',
        trigger_reason: '',
        evidence_references: [],
        notes: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            due_date: payload.due_date || null,
            competency_standard: payload.competency_standard || null,
            trigger_reason: payload.trigger_reason || null,
            notes: payload.notes || null,
        }));

        post(`/operations-manual-revisions/${manualRevision.id}/training-requirements`);
    }

    return (
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Training requirement" error={errors.title}><Input value={data.title} onChange={(event) => setData('title', event.target.value)} placeholder="Emergency procedure amendment briefing" /></Field>
                <Field label="Type" error={errors.requirement_type}><OptionSelect value={data.requirement_type} options={options.types} onChange={(value) => setData('requirement_type', value)} /></Field>
                <Field label="Status" error={errors.training_status}><OptionSelect value={data.training_status} options={options.statuses} onChange={(value) => setData('training_status', value)} /></Field>
                <Field label="Due date" error={errors.due_date}><Input type="date" value={data.due_date} onChange={(event) => setData('due_date', event.target.value)} /></Field>
                <Field label="Competency standard" error={errors.competency_standard}><Input value={data.competency_standard} onChange={(event) => setData('competency_standard', event.target.value)} /></Field>
            </section>

            <section className="grid gap-5 md:grid-cols-2">
                <TextList label="Affected roles" value={data.affected_roles} onChange={(value) => setData('affected_roles', value)} error={errors.affected_roles} />
                <TextList label="Evidence references" value={data.evidence_references} onChange={(value) => setData('evidence_references', value)} error={errors.evidence_references} />
                <Field label="Trigger reason" error={errors.trigger_reason}><textarea value={data.trigger_reason} onChange={(event) => setData('trigger_reason', event.target.value)} className="min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" /></Field>
                <Field label="Notes" error={errors.notes}><textarea value={data.notes} onChange={(event) => setData('notes', event.target.value)} className="min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" /></Field>
            </section>

            <div className="flex flex-wrap items-center gap-3">
                <Button disabled={processing}><Save />Save requirement</Button>
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
