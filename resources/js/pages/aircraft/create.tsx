import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useMemo } from 'react';
import { AircraftModel } from './catalogue/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Aircraft', href: '/aircraft' }, { title: 'Add Aircraft', href: '/aircraft/create' }];

interface Options {
    catalogue_models: Array<{ id: number; label: string; manufacturer: string; model: string; summary: AircraftModel }>;
    operators: Array<{ id: number; label: string }>;
    onboarding_statuses: Record<string, string>;
}

export default function Create({ options }: { options: Options }) {
    const initialModelId = new URLSearchParams(window.location.search).get('aircraft_model_id') || '';
    const { data, setData, post, processing, errors } = useForm({
        aircraft_model_id: initialModelId,
        uas_operator_id: '',
        manufacturer: '',
        model: '',
        registration: '',
        serial_number: '',
        internal_asset_number: '',
        owner: '',
        supplier: '',
        firmware_version: '',
        flight_controller_serial: '',
        remote_id_serial: '',
        acquisition_date: '',
        operational_status: 'pending_registration',
        onboarding_status: 'onboarding',
        base_location: '',
    });
    const selectedModel = useMemo(() => options.catalogue_models.find((model) => model.id.toString() === data.aircraft_model_id)?.summary, [data.aircraft_model_id, options.catalogue_models]);

    function submit(event: FormEvent) {
        event.preventDefault();
        post('/aircraft');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Aircraft" />
            <form onSubmit={submit} className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Add Aircraft" description="Start with a governed catalogue model, then capture the physical aircraft identity and operator-specific configuration." />
                <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
                    <h2 className="mb-4 text-base font-semibold">Select your aircraft</h2>
                    <SelectField label="Catalogue model" value={data.aircraft_model_id} onChange={(value) => setData('aircraft_model_id', value)} options={options.catalogue_models} placeholder="Select manufacturer and model" error={errors.aircraft_model_id} />
                    {selectedModel && <div className="mt-4 grid gap-2 rounded-md border p-3 text-sm sm:grid-cols-3"><Detail label="Model" value={`${selectedModel.manufacturer.name} ${selectedModel.model}`} /><Detail label="Flight time" value={selectedModel.max_flight_time_min ? `${selectedModel.max_flight_time_min} min` : null} /><Detail label="MTOW" value={selectedModel.mtow_kg ? `${selectedModel.mtow_kg} kg` : null} /><Detail label="Batteries" value={selectedModel.package_counts.batteries ? `${selectedModel.package_counts.batteries} package item(s)` : null} /><Detail label="Components" value={selectedModel.package_counts.components ? `${selectedModel.package_counts.components} package item(s)` : null} /><Detail label="Maintenance baseline" value={selectedModel.package_counts.maintenance_baselines ? `${selectedModel.package_counts.maintenance_baselines} rule(s)` : selectedModel.package_status.replaceAll('_', ' ')} /></div>}
                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        <Field label="Custom manufacturer" error={errors.manufacturer}><Input value={data.manufacturer} onChange={(event) => setData('manufacturer', event.target.value)} disabled={!!selectedModel} /></Field>
                        <Field label="Custom model" error={errors.model}><Input value={data.model} onChange={(event) => setData('model', event.target.value)} disabled={!!selectedModel} /></Field>
                    </div>
                </section>
                <section className="grid gap-4 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-2">
                    <SelectField label="Operator" value={data.uas_operator_id} onChange={(value) => setData('uas_operator_id', value)} options={options.operators} placeholder="Assign operator" error={errors.uas_operator_id} />
                    <SelectMap label="Onboarding status" value={data.onboarding_status} onChange={(value) => setData('onboarding_status', value)} options={options.onboarding_statuses} error={errors.onboarding_status} />
                    <Field label="Registration mark" error={errors.registration}><Input value={data.registration} onChange={(event) => setData('registration', event.target.value)} /></Field>
                    <Field label="Serial number" error={errors.serial_number}><Input value={data.serial_number} onChange={(event) => setData('serial_number', event.target.value)} /></Field>
                    <Field label="Internal asset number" error={errors.internal_asset_number}><Input value={data.internal_asset_number} onChange={(event) => setData('internal_asset_number', event.target.value)} /></Field>
                    <Field label="Base location" error={errors.base_location}><Input value={data.base_location} onChange={(event) => setData('base_location', event.target.value)} /></Field>
                    <Field label="Owner" error={errors.owner}><Input value={data.owner} onChange={(event) => setData('owner', event.target.value)} /></Field>
                    <Field label="Supplier" error={errors.supplier}><Input value={data.supplier} onChange={(event) => setData('supplier', event.target.value)} /></Field>
                    <Field label="Firmware version" error={errors.firmware_version}><Input value={data.firmware_version} onChange={(event) => setData('firmware_version', event.target.value)} /></Field>
                    <Field label="Flight controller serial" error={errors.flight_controller_serial}><Input value={data.flight_controller_serial} onChange={(event) => setData('flight_controller_serial', event.target.value)} /></Field>
                    <Field label="Remote ID serial" error={errors.remote_id_serial}><Input value={data.remote_id_serial} onChange={(event) => setData('remote_id_serial', event.target.value)} /></Field>
                    <Field label="Acquisition date" error={errors.acquisition_date}><Input type="date" value={data.acquisition_date} onChange={(event) => setData('acquisition_date', event.target.value)} /></Field>
                </section>
                <div className="flex gap-3"><Button disabled={processing}>Create aircraft</Button><Button variant="outline" asChild><Link href="/aircraft">Cancel</Link></Button></div>
            </form>
        </AppLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <div className="space-y-2"><Label>{label}</Label>{children}{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectField({ label, value, onChange, options, placeholder, error }: { label: string; value: string; onChange: (value: string) => void; options: Array<{ id: number; label: string }>; placeholder: string; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue placeholder={placeholder} /></SelectTrigger><SelectContent>{options.map((option) => <SelectItem key={option.id} value={option.id.toString()}>{option.label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectMap({ label, value, onChange, options, error }: { label: string; value: string; onChange: (value: string) => void; options: Record<string, string>; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(options).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd>{value || 'Not captured'}</dd></div>;
}
