import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { OperatorOptions, OperatorProfile } from './types';

type OperatorFormData = {
    legal_entity: string;
    trading_name: string;
    registration_number: string;
    uasoc_number: string;
    certificate_issue_date: string;
    certificate_expiry_date: string;
    status: string;
    accountable_manager: string;
    responsible_person_flight_operations: string;
    responsible_person_aircraft: string;
    safety_manager: string;
    security_coordinator: string;
    operating_bases: string[];
    approved_aircraft: string[];
    approved_pilots: string[];
    operations_specifications: string[];
    evidence_references: string[];
};

export default function OperatorForm({ options, operator }: { options: OperatorOptions; operator?: OperatorProfile }) {
    const { data, setData, post, put, processing, errors, transform } = useForm<OperatorFormData>({
        legal_entity: operator?.legal_entity ?? '',
        trading_name: operator?.trading_name ?? '',
        registration_number: operator?.registration_number ?? '',
        uasoc_number: operator?.uasoc_number ?? '',
        certificate_issue_date: operator?.certificate_issue_date ?? '',
        certificate_expiry_date: operator?.certificate_expiry_date ?? '',
        status: operator?.status ?? 'draft',
        accountable_manager: operator?.accountable_manager ?? '',
        responsible_person_flight_operations: operator?.responsible_person_flight_operations ?? '',
        responsible_person_aircraft: operator?.responsible_person_aircraft ?? '',
        safety_manager: operator?.safety_manager ?? '',
        security_coordinator: operator?.security_coordinator ?? '',
        operating_bases: operator?.operating_bases ?? [],
        approved_aircraft: operator?.approved_aircraft?.map(String) ?? [],
        approved_pilots: operator?.approved_pilots?.map(String) ?? [],
        operations_specifications: operator?.operations_specifications ?? [],
        evidence_references: operator?.evidence_references ?? [],
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            trading_name: payload.trading_name || null,
            registration_number: payload.registration_number || null,
            uasoc_number: payload.uasoc_number || null,
            certificate_issue_date: payload.certificate_issue_date || null,
            certificate_expiry_date: payload.certificate_expiry_date || null,
            safety_manager: payload.safety_manager || null,
            security_coordinator: payload.security_coordinator || null,
            approved_aircraft: payload.approved_aircraft.map(Number),
            approved_pilots: payload.approved_pilots.map(Number),
        }));

        if (operator) {
            put(`/operators/${operator.id}`);
            return;
        }

        post('/operators');
    }

    return (
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Legal entity" error={errors.legal_entity}><Input value={data.legal_entity} onChange={(event) => setData('legal_entity', event.target.value)} required /></Field>
                <Field label="Trading name" error={errors.trading_name}><Input value={data.trading_name} onChange={(event) => setData('trading_name', event.target.value)} /></Field>
                <Field label="Registration number" error={errors.registration_number}><Input value={data.registration_number} onChange={(event) => setData('registration_number', event.target.value)} /></Field>
                <Field label="UASOC / ROC number" error={errors.uasoc_number}><Input value={data.uasoc_number} onChange={(event) => setData('uasoc_number', event.target.value)} /></Field>
                <Field label="Issue date" error={errors.certificate_issue_date}><Input type="date" value={data.certificate_issue_date} onChange={(event) => setData('certificate_issue_date', event.target.value)} /></Field>
                <Field label="Expiry date" error={errors.certificate_expiry_date}><Input type="date" value={data.certificate_expiry_date} onChange={(event) => setData('certificate_expiry_date', event.target.value)} /></Field>
                <Field label="Status" error={errors.status}><OptionSelect value={data.status} options={options.statuses} onChange={(value) => setData('status', value)} /></Field>
            </section>

            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Accountable manager" error={errors.accountable_manager}><Input value={data.accountable_manager} onChange={(event) => setData('accountable_manager', event.target.value)} required /></Field>
                <Field label="Responsible person: flight operations" error={errors.responsible_person_flight_operations}><Input value={data.responsible_person_flight_operations} onChange={(event) => setData('responsible_person_flight_operations', event.target.value)} required /></Field>
                <Field label="Responsible person: aircraft" error={errors.responsible_person_aircraft}><Input value={data.responsible_person_aircraft} onChange={(event) => setData('responsible_person_aircraft', event.target.value)} required /></Field>
                <Field label="Safety manager" error={errors.safety_manager}><Input value={data.safety_manager} onChange={(event) => setData('safety_manager', event.target.value)} /></Field>
                <Field label="Security coordinator" error={errors.security_coordinator}><Input value={data.security_coordinator} onChange={(event) => setData('security_coordinator', event.target.value)} /></Field>
            </section>

            <section className="grid gap-5 md:grid-cols-2">
                <Field label="Operating bases" error={errors.operating_bases}><TagInput value={data.operating_bases.join('\n')} onChange={(value) => setData('operating_bases', lines(value))} /></Field>
                <Field label="Operations specifications" error={errors.operations_specifications}><TagInput value={data.operations_specifications.join('\n')} onChange={(value) => setData('operations_specifications', lines(value))} /></Field>
                <Field label="Evidence references" error={errors.evidence_references}><TagInput value={data.evidence_references.join('\n')} onChange={(value) => setData('evidence_references', lines(value))} /></Field>
                <Checklist label="Approved aircraft" options={options.aircraft} selected={data.approved_aircraft} onChange={(value) => setData('approved_aircraft', value)} error={errors.approved_aircraft} />
                <Checklist label="Approved pilots" options={options.pilots} selected={data.approved_pilots} onChange={(value) => setData('approved_pilots', value)} error={errors.approved_pilots} />
            </section>

            <div className="flex flex-wrap items-center gap-3">
                <Button disabled={processing}><Save />Save operator</Button>
                <Button variant="outline" asChild><Link href="/operators">Cancel</Link></Button>
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

function TagInput({ value, onChange }: { value: string; onChange: (value: string) => void }) {
    return <textarea value={value} onChange={(event) => onChange(event.target.value)} className="min-h-28 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" />;
}

function Checklist({ label, options, selected, onChange, error }: { label: string; options: Array<{ id: number; label: string }>; selected: string[]; onChange: (value: string[]) => void; error?: string }) {
    function toggle(id: string) {
        onChange(selected.includes(id) ? selected.filter((value) => value !== id) : [...selected, id]);
    }

    return <div><Label>{label}</Label><div className="mt-2 grid gap-2 rounded-md border p-3">{options.length === 0 ? <p className="text-sm text-muted-foreground">No records available.</p> : options.map((option) => <label key={option.id} className="flex items-center gap-2 text-sm"><input type="checkbox" checked={selected.includes(option.id.toString())} onChange={() => toggle(option.id.toString())} />{option.label}</label>)}</div><InputError message={error} className="mt-2" /></div>;
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>;
}