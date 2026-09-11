import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type AircraftOption = { id: number; label: string };

type BatteryForm = {
    battery_uid: string;
    manufacturer: string;
    model: string;
    serial_number: string;
    compatible_uas_aircraft_id: string;
    cycle_count: string;
    maximum_cycles: string;
    acquisition_date: string;
    damage_incidents: string;
    retirement_status: string;
    charge_history: string[];
    evidence_references: string[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Batteries', href: '/batteries' },
    { title: 'Create', href: '/batteries/create' },
];

export default function Create({ aircraft }: { aircraft: AircraftOption[] }) {
    const [chargeHistoryText, setChargeHistoryText] = useState('');
    const [evidenceText, setEvidenceText] = useState('');
    const { data, setData, post, processing, errors, transform } = useForm<BatteryForm>({
        battery_uid: '',
        manufacturer: '',
        model: '',
        serial_number: '',
        compatible_uas_aircraft_id: '',
        cycle_count: '0',
        maximum_cycles: '',
        acquisition_date: '',
        damage_incidents: '',
        retirement_status: 'active',
        charge_history: [],
        evidence_references: [],
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((values) => ({
            ...values,
            model: values.model || null,
            compatible_uas_aircraft_id: values.compatible_uas_aircraft_id || null,
            cycle_count: values.cycle_count || 0,
            maximum_cycles: values.maximum_cycles || null,
            acquisition_date: values.acquisition_date || null,
            damage_incidents: values.damage_incidents || null,
            charge_history: lines(chargeHistoryText),
            evidence_references: lines(evidenceText),
        }));
        post('/batteries');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create battery" />
            <form onSubmit={submit} className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Create battery" description="Capture battery identity, compatibility, cycles, charge history and retirement status." actions={<Button variant="outline" asChild><Link href="/batteries">Back</Link></Button>} />

                <section className="grid gap-4 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-2">
                    <Field label="Battery ID" error={errors.battery_uid}><Input value={data.battery_uid} onChange={(event) => setData('battery_uid', event.target.value)} /></Field>
                    <Field label="Serial number" error={errors.serial_number}><Input value={data.serial_number} onChange={(event) => setData('serial_number', event.target.value)} /></Field>
                    <Field label="Manufacturer" error={errors.manufacturer}><Input value={data.manufacturer} onChange={(event) => setData('manufacturer', event.target.value)} /></Field>
                    <Field label="Model" error={errors.model}><Input value={data.model} onChange={(event) => setData('model', event.target.value)} /></Field>
                    <SelectField label="Compatible aircraft" value={data.compatible_uas_aircraft_id} onChange={(value) => setData('compatible_uas_aircraft_id', value)} options={aircraft} placeholder="Fleet compatible" error={errors.compatible_uas_aircraft_id} />
                    <SelectMap label="Retirement status" value={data.retirement_status} onChange={(value) => setData('retirement_status', value)} options={{ active: 'Active', quarantined: 'Quarantined', retired: 'Retired' }} error={errors.retirement_status} />
                    <Field label="Current cycles" error={errors.cycle_count}><Input value={data.cycle_count} onChange={(event) => setData('cycle_count', event.target.value)} /></Field>
                    <Field label="Maximum cycles" error={errors.maximum_cycles}><Input value={data.maximum_cycles} onChange={(event) => setData('maximum_cycles', event.target.value)} /></Field>
                    <Field label="Acquisition date" error={errors.acquisition_date}><Input type="date" value={data.acquisition_date} onChange={(event) => setData('acquisition_date', event.target.value)} /></Field>
                </section>

                <section className="grid gap-4 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-2">
                    <Field label="Charge history" error={errors.charge_history}><TextArea value={chargeHistoryText} onChange={(event) => setChargeHistoryText(event.target.value)} /></Field>
                    <Field label="Evidence references" error={errors.evidence_references}><TextArea value={evidenceText} onChange={(event) => setEvidenceText(event.target.value)} /></Field>
                    <Field label="Damage / incidents" error={errors.damage_incidents}><TextArea value={data.damage_incidents} onChange={(event) => setData('damage_incidents', event.target.value)} /></Field>
                </section>

                <div className="flex gap-3">
                    <Button disabled={processing}>Create battery</Button>
                    <Button variant="outline" asChild><Link href="/batteries">Cancel</Link></Button>
                </div>
            </form>
        </AppLayout>
    );
}

function lines(value: string) {
    return value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <div className="space-y-2"><Label>{label}</Label>{children}{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectField({ label, value, onChange, options, placeholder, error }: { label: string; value: string; onChange: (value: string) => void; options: AircraftOption[]; placeholder: string; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue placeholder={placeholder} /></SelectTrigger><SelectContent>{options.map((option) => <SelectItem key={option.id} value={option.id.toString()}>{option.label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectMap({ label, value, onChange, options, error }: { label: string; value: string; onChange: (value: string) => void; options: Record<string, string>; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(options).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function TextArea({ value, onChange }: { value: string; onChange: (event: React.ChangeEvent<HTMLTextAreaElement>) => void }) {
    return <textarea value={value} onChange={onChange} className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm" />;
}