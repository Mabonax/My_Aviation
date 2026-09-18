import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { GisProjectOptions } from './types';

type GisProjectFormData = {
    project_code: string;
    name: string;
    project_type: string;
    client_or_stakeholder: string;
    area_name: string;
    location_search_query: string;
    centroid_latitude: string;
    centroid_longitude: string;
    source_reference: string;
    source_version: string;
    data_governance_notes: string;
    evidence_required: string;
    responsible_role: string;
};

export default function Create({ options }: { options: GisProjectOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'GIS projects', href: '/gis-projects' }, { title: 'New', href: '/gis-projects/create' }];
    const { data, setData, post, processing, errors, transform } = useForm<GisProjectFormData>({
        project_code: '',
        name: '',
        project_type: 'environmental_mapping',
        client_or_stakeholder: '',
        area_name: '',
        location_search_query: '',
        centroid_latitude: '',
        centroid_longitude: '',
        source_reference: 'UAS Compliance & Operations Platform FRS section 32 and Phase 5 section 41',
        source_version: 'FRS v1.0, dated 2026-09-09',
        data_governance_notes: '',
        evidence_required: 'Project brief, authorised mission records, dataset provenance and final report evidence',
        responsible_role: 'GIS Lead',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            client_or_stakeholder: payload.client_or_stakeholder || null,
            location_search_query: payload.location_search_query || null,
            centroid_latitude: payload.centroid_latitude || null,
            centroid_longitude: payload.centroid_longitude || null,
            source_version: payload.source_version || null,
            data_governance_notes: payload.data_governance_notes || null,
        }));
        post('/gis-projects');
    }

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="New GIS project" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="New GIS project" description="Project spine for mapping work from planning through report." />
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Project code" error={errors.project_code}><Input value={data.project_code} onChange={(event) => setData('project_code', event.target.value)} /></Field>
                <Field label="Name" error={errors.name}><Input value={data.name} onChange={(event) => setData('name', event.target.value)} /></Field>
                <Field label="Project type" error={errors.project_type}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.project_type} onChange={(event) => setData('project_type', event.target.value)}>{Object.entries(options.project_types).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                <Field label="Client or stakeholder" error={errors.client_or_stakeholder}><Input value={data.client_or_stakeholder} onChange={(event) => setData('client_or_stakeholder', event.target.value)} /></Field>
                <Field label="Area name" error={errors.area_name}><Input value={data.area_name} onChange={(event) => setData('area_name', event.target.value)} /></Field>
                <Field label="Location search" error={errors.location_search_query}><Input value={data.location_search_query} onChange={(event) => setData('location_search_query', event.target.value)} /></Field>
                <Field label="Centroid latitude" error={errors.centroid_latitude}><Input value={data.centroid_latitude} onChange={(event) => setData('centroid_latitude', event.target.value)} /></Field>
                <Field label="Centroid longitude" error={errors.centroid_longitude}><Input value={data.centroid_longitude} onChange={(event) => setData('centroid_longitude', event.target.value)} /></Field>
                <Field label="Responsible role" error={errors.responsible_role}><Input value={data.responsible_role} onChange={(event) => setData('responsible_role', event.target.value)} /></Field>
                <Field label="Source reference" error={errors.source_reference}><Input value={data.source_reference} onChange={(event) => setData('source_reference', event.target.value)} /></Field>
                <Field label="Source version" error={errors.source_version}><Input value={data.source_version} onChange={(event) => setData('source_version', event.target.value)} /></Field>
                <Field label="Evidence required" error={errors.evidence_required}><Input value={data.evidence_required} onChange={(event) => setData('evidence_required', event.target.value)} /></Field>
            </section>
            <Field label="Data governance notes" error={errors.data_governance_notes}><textarea className="min-h-32 w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.data_governance_notes} onChange={(event) => setData('data_governance_notes', event.target.value)} /></Field>
            <Button disabled={processing}><Save />Save project</Button>
        </form>
    </div></AppLayout>;
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) { return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>; }
