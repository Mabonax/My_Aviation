import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { RegulatoryFee } from './types';

type RegulatoryFeeFormData = {
    regulation_part: string;
    transaction_code: string;
    description: string;
    amount: string;
    currency: string;
    effective_from: string;
    effective_to: string;
    source: string;
    source_version: string;
    status: string;
    verified_at: string;
};

export default function FeeForm({ fee, superseding = false }: { fee?: RegulatoryFee; superseding?: boolean }) {
    const { data, setData, post, processing, errors, transform } = useForm<RegulatoryFeeFormData>({
        regulation_part: fee?.regulation_part ?? '',
        transaction_code: fee?.transaction_code ?? '',
        description: fee?.description ?? '',
        amount: '',
        currency: fee?.currency ?? 'ZAR',
        effective_from: '',
        effective_to: '',
        source: fee?.source ?? '',
        source_version: '',
        status: 'active',
        verified_at: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            amount: payload.amount || null,
            effective_to: payload.effective_to || null,
            verified_at: payload.verified_at || null,
        }));
        post(superseding && fee ? `/regulatory-fees/${fee.id}/supersede` : '/regulatory-fees');
    }

    return (
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Regulation part" error={errors.regulation_part}><Input value={data.regulation_part} onChange={(event) => setData('regulation_part', event.target.value)} /></Field>
                <Field label="Transaction code" error={errors.transaction_code}><Input value={data.transaction_code} onChange={(event) => setData('transaction_code', event.target.value)} /></Field>
                <Field label="Description" error={errors.description}><Input value={data.description} onChange={(event) => setData('description', event.target.value)} /></Field>
                <Field label="Amount" error={errors.amount}><Input type="number" min="0" step="0.01" value={data.amount} onChange={(event) => setData('amount', event.target.value)} /></Field>
                <Field label="Currency" error={errors.currency}><Input maxLength={3} value={data.currency} onChange={(event) => setData('currency', event.target.value.toUpperCase())} /></Field>
                <Field label="Effective from" error={errors.effective_from}><Input type="date" value={data.effective_from} onChange={(event) => setData('effective_from', event.target.value)} /></Field>
                <Field label="Effective to" error={errors.effective_to}><Input type="date" value={data.effective_to} onChange={(event) => setData('effective_to', event.target.value)} /></Field>
                <Field label="Source version" error={errors.source_version}><Input value={data.source_version} onChange={(event) => setData('source_version', event.target.value)} /></Field>
                {!superseding && <Field label="Status" error={errors.status}><Input value={data.status} onChange={(event) => setData('status', event.target.value)} /></Field>}
                <Field label="Verified at" error={errors.verified_at}><Input type="datetime-local" value={data.verified_at} onChange={(event) => setData('verified_at', event.target.value)} /></Field>
                <Field label="Source" error={errors.source}><Input value={data.source} onChange={(event) => setData('source', event.target.value)} /></Field>
            </section>
            <Button disabled={processing}><Save />{superseding ? 'Create tariff version' : 'Save fee'}</Button>
        </form>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) { return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>; }
