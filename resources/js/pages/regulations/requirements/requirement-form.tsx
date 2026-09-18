import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { RegulatoryRequirement } from './types';

type RequirementFormData = {
    requirement_id: string;
    regulation_part: string;
    clause_reference: string;
    title: string;
    requirement_text: string;
    responsible_party: string;
    applicability: string;
    system_control: string;
    evidence_required: string;
    frequency: string;
    validity_period: string;
    retention_period: string;
    effective_date: string;
    superseded_date: string;
    official_source: string;
    source_version: string;
    status: string;
};

export default function RequirementForm({ requirement, superseding = false }: { requirement?: RegulatoryRequirement; superseding?: boolean }) {
    const { data, setData, post, processing, errors, transform } = useForm<RequirementFormData>({
        requirement_id: superseding && requirement ? `${requirement.requirement_id}-REV` : '',
        regulation_part: requirement?.regulation_part ?? '',
        clause_reference: requirement?.clause_reference ?? '',
        title: requirement?.title ?? '',
        requirement_text: requirement?.requirement_text ?? '',
        responsible_party: requirement?.responsible_party ?? '',
        applicability: requirement?.applicability ?? '',
        system_control: requirement?.system_control ?? '',
        evidence_required: requirement?.evidence_required ?? '',
        frequency: requirement?.frequency ?? '',
        validity_period: requirement?.validity_period ?? '',
        retention_period: requirement?.retention_period ?? '',
        effective_date: '',
        superseded_date: '',
        official_source: requirement?.official_source ?? '',
        source_version: '',
        status: 'active',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            clause_reference: payload.clause_reference || null,
            evidence_required: payload.evidence_required || null,
            frequency: payload.frequency || null,
            validity_period: payload.validity_period || null,
            retention_period: payload.retention_period || null,
            superseded_date: payload.superseded_date || null,
        }));
        post(superseding && requirement ? `/regulatory-requirements/${requirement.id}/supersede` : '/regulatory-requirements');
    }

    return (
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Requirement ID" error={errors.requirement_id}><Input value={data.requirement_id} onChange={(event) => setData('requirement_id', event.target.value)} /></Field>
                <Field label="Regulation part" error={errors.regulation_part}><Input value={data.regulation_part} onChange={(event) => setData('regulation_part', event.target.value)} /></Field>
                <Field label="Clause reference" error={errors.clause_reference}><Input value={data.clause_reference} onChange={(event) => setData('clause_reference', event.target.value)} /></Field>
                <Field label="Title" error={errors.title}><Input value={data.title} onChange={(event) => setData('title', event.target.value)} /></Field>
                <Field label="Responsible party" error={errors.responsible_party}><Input value={data.responsible_party} onChange={(event) => setData('responsible_party', event.target.value)} /></Field>
                <Field label="Effective date" error={errors.effective_date}><Input type="date" value={data.effective_date} onChange={(event) => setData('effective_date', event.target.value)} /></Field>
                {!superseding && <Field label="Status" error={errors.status}><Input value={data.status} onChange={(event) => setData('status', event.target.value)} /></Field>}
                <Field label="Source version" error={errors.source_version}><Input value={data.source_version} onChange={(event) => setData('source_version', event.target.value)} /></Field>
                <Field label="Official source" error={errors.official_source}><Input value={data.official_source} onChange={(event) => setData('official_source', event.target.value)} /></Field>
                <Field label="Frequency" error={errors.frequency}><Input value={data.frequency} onChange={(event) => setData('frequency', event.target.value)} /></Field>
                <Field label="Validity period" error={errors.validity_period}><Input value={data.validity_period} onChange={(event) => setData('validity_period', event.target.value)} /></Field>
                <Field label="Retention period" error={errors.retention_period}><Input value={data.retention_period} onChange={(event) => setData('retention_period', event.target.value)} /></Field>
            </section>
            <section className="grid gap-5 lg:grid-cols-2">
                <Field label="Requirement" error={errors.requirement_text}><TextArea value={data.requirement_text} onChange={(value) => setData('requirement_text', value)} /></Field>
                <Field label="Applicability" error={errors.applicability}><TextArea value={data.applicability} onChange={(value) => setData('applicability', value)} /></Field>
                <Field label="System control" error={errors.system_control}><TextArea value={data.system_control} onChange={(value) => setData('system_control', value)} /></Field>
                <Field label="Evidence required" error={errors.evidence_required}><TextArea value={data.evidence_required} onChange={(value) => setData('evidence_required', value)} /></Field>
            </section>
            <Button disabled={processing}><Save />{superseding ? 'Create version' : 'Save requirement'}</Button>
        </form>
    );
}

function TextArea({ value, onChange }: { value: string; onChange: (value: string) => void }) { return <textarea value={value} onChange={(event) => onChange(event.target.value)} className="min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />; }
function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) { return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>; }
