import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { RegulatoryForm } from './types';

type RegulatoryFormData = {
    form_code: string;
    form_title: string;
    regulatory_area: string;
    revision: string;
    effective_date: string;
    superseded_date: string;
    source_reference: string;
    source_url: string;
    required_transaction: string;
    status: string;
    verified_at: string;
};

export default function FormRegisterForm({ form, superseding = false }: { form?: RegulatoryForm; superseding?: boolean }) {
    const { data, setData, post, processing, errors, transform } = useForm<RegulatoryFormData>({
        form_code: form?.form_code ?? '',
        form_title: form?.form_title ?? '',
        regulatory_area: form?.regulatory_area ?? '',
        revision: superseding && form ? `${form.revision}-REV` : '',
        effective_date: '',
        superseded_date: '',
        source_reference: form?.source_reference ?? '',
        source_url: form?.source_url ?? '',
        required_transaction: form?.required_transaction ?? '',
        status: 'active',
        verified_at: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            superseded_date: payload.superseded_date || null,
            source_url: payload.source_url || null,
            verified_at: payload.verified_at || null,
        }));
        post(superseding && form ? `/regulatory-forms/${form.id}/supersede` : '/regulatory-forms');
    }

    return (
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Form code" error={errors.form_code}><Input value={data.form_code} onChange={(event) => setData('form_code', event.target.value)} /></Field>
                <Field label="Form title" error={errors.form_title}><Input value={data.form_title} onChange={(event) => setData('form_title', event.target.value)} /></Field>
                <Field label="Regulatory area" error={errors.regulatory_area}><Input value={data.regulatory_area} onChange={(event) => setData('regulatory_area', event.target.value)} /></Field>
                <Field label="Revision" error={errors.revision}><Input value={data.revision} onChange={(event) => setData('revision', event.target.value)} /></Field>
                <Field label="Effective date" error={errors.effective_date}><Input type="date" value={data.effective_date} onChange={(event) => setData('effective_date', event.target.value)} /></Field>
                {!superseding && <Field label="Superseded date" error={errors.superseded_date}><Input type="date" value={data.superseded_date} onChange={(event) => setData('superseded_date', event.target.value)} /></Field>}
                <Field label="Required transaction" error={errors.required_transaction}><Input value={data.required_transaction} onChange={(event) => setData('required_transaction', event.target.value)} /></Field>
                {!superseding && <Field label="Status" error={errors.status}><Input value={data.status} onChange={(event) => setData('status', event.target.value)} /></Field>}
                <Field label="Verified at" error={errors.verified_at}><Input type="datetime-local" value={data.verified_at} onChange={(event) => setData('verified_at', event.target.value)} /></Field>
                <Field label="Source reference" error={errors.source_reference}><Input value={data.source_reference} onChange={(event) => setData('source_reference', event.target.value)} /></Field>
                <Field label="Source URL" error={errors.source_url}><Input value={data.source_url} onChange={(event) => setData('source_url', event.target.value)} /></Field>
            </section>
            <Button disabled={processing}><Save />{superseding ? 'Create version' : 'Save form'}</Button>
        </form>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) { return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>; }
