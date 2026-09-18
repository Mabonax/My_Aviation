import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Plus, Save, Trash2 } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { GisDatasetOptions } from '../types';

type ProjectMissionContext = {
    id: number;
    mapping_objective: string;
    project: { id: number; project_code: string; name: string };
    mission: { id: number; mission_number: string; purpose: string };
};

type LayerFormData = {
    layer_name: string;
    layer_type: string;
    geometry_type: string;
    source_uri: string;
    analysis_notes: string;
    status: string;
};

type DatasetFormData = {
    dataset_code: string;
    title: string;
    dataset_type: string;
    capture_source: string;
    storage_uri: string;
    checksum: string;
    coordinate_reference_system: string;
    resolution_cm: string;
    captured_at: string;
    processed_at: string;
    processing_status: string;
    quality_status: string;
    provenance_notes: string;
    evidence_notes: string;
    layers: LayerFormData[];
};

export default function Create({ projectMission, options }: { projectMission: ProjectMissionContext; options: GisDatasetOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'GIS projects', href: '/gis-projects' }, { title: projectMission.project.project_code, href: `/gis-projects/${projectMission.project.id}` }, { title: 'Dataset', href: `/gis-project-missions/${projectMission.id}/datasets/create` }];
    const { data, setData, post, processing, errors, transform } = useForm<DatasetFormData>({
        dataset_code: '',
        title: '',
        dataset_type: 'imagery',
        capture_source: 'UAS mission capture',
        storage_uri: '',
        checksum: '',
        coordinate_reference_system: 'WGS84',
        resolution_cm: '',
        captured_at: '',
        processed_at: '',
        processing_status: 'raw',
        quality_status: 'unchecked',
        provenance_notes: '',
        evidence_notes: '',
        layers: [],
    });

    function addLayer() {
        setData('layers', [...data.layers, { layer_name: '', layer_type: 'orthomosaic_reference', geometry_type: 'raster', source_uri: '', analysis_notes: '', status: 'draft' }]);
    }

    function updateLayer(index: number, key: keyof LayerFormData, value: string) {
        setData('layers', data.layers.map((layer, current) => current === index ? { ...layer, [key]: value } : layer));
    }

    function removeLayer(index: number) {
        setData('layers', data.layers.filter((_, current) => current !== index));
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            checksum: payload.checksum || null,
            coordinate_reference_system: payload.coordinate_reference_system || null,
            resolution_cm: payload.resolution_cm || null,
            captured_at: payload.captured_at || null,
            processed_at: payload.processed_at || null,
            evidence_notes: payload.evidence_notes || null,
            layers: payload.layers.map((layer) => ({ ...layer, source_uri: layer.source_uri || null, analysis_notes: layer.analysis_notes || null })),
        }));
        post(`/gis-project-missions/${projectMission.id}/datasets`);
    }

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Capture GIS dataset" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Capture GIS dataset" description={`${projectMission.mission.mission_number} / ${projectMission.mapping_objective}`} />
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Dataset code" error={errors.dataset_code}><Input value={data.dataset_code} onChange={(event) => setData('dataset_code', event.target.value)} /></Field>
                <Field label="Title" error={errors.title}><Input value={data.title} onChange={(event) => setData('title', event.target.value)} /></Field>
                <Field label="Dataset type" error={errors.dataset_type}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.dataset_type} onChange={(event) => setData('dataset_type', event.target.value)}>{Object.entries(options.dataset_types).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                <Field label="Capture source" error={errors.capture_source}><Input value={data.capture_source} onChange={(event) => setData('capture_source', event.target.value)} /></Field>
                <Field label="Storage URI" error={errors.storage_uri}><Input value={data.storage_uri} onChange={(event) => setData('storage_uri', event.target.value)} /></Field>
                <Field label="Checksum" error={errors.checksum}><Input value={data.checksum} onChange={(event) => setData('checksum', event.target.value)} /></Field>
                <Field label="CRS" error={errors.coordinate_reference_system}><Input value={data.coordinate_reference_system} onChange={(event) => setData('coordinate_reference_system', event.target.value)} /></Field>
                <Field label="Resolution cm" error={errors.resolution_cm}><Input value={data.resolution_cm} onChange={(event) => setData('resolution_cm', event.target.value)} /></Field>
                <Field label="Captured at" error={errors.captured_at}><Input type="datetime-local" value={data.captured_at} onChange={(event) => setData('captured_at', event.target.value)} /></Field>
                <Field label="Processed at" error={errors.processed_at}><Input type="datetime-local" value={data.processed_at} onChange={(event) => setData('processed_at', event.target.value)} /></Field>
                <Field label="Processing" error={errors.processing_status}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.processing_status} onChange={(event) => setData('processing_status', event.target.value)}>{Object.entries(options.processing_statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                <Field label="Quality" error={errors.quality_status}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.quality_status} onChange={(event) => setData('quality_status', event.target.value)}>{Object.entries(options.quality_statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
            </section>
            <Field label="Provenance notes" error={errors.provenance_notes}><textarea className="min-h-32 w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.provenance_notes} onChange={(event) => setData('provenance_notes', event.target.value)} /></Field>
            <Field label="Evidence notes" error={errors.evidence_notes}><textarea className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.evidence_notes} onChange={(event) => setData('evidence_notes', event.target.value)} /></Field>
            <section className="space-y-4">
                <div className="flex items-center justify-between gap-3"><h2 className="text-base font-semibold">Spatial Layers</h2><Button type="button" variant="outline" onClick={addLayer}>Add Layer<Plus /></Button></div>
                {data.layers.map((layer, index) => <div key={index} className="grid gap-4 rounded-lg border p-4 md:grid-cols-2 xl:grid-cols-4">
                    <Field label="Layer name" error={(errors as Record<string, string>)[`layers.${index}.layer_name`]}><Input value={layer.layer_name} onChange={(event) => updateLayer(index, 'layer_name', event.target.value)} /></Field>
                    <Field label="Layer type" error={(errors as Record<string, string>)[`layers.${index}.layer_type`]}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={layer.layer_type} onChange={(event) => updateLayer(index, 'layer_type', event.target.value)}>{Object.entries(options.layer_types).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                    <Field label="Geometry" error={(errors as Record<string, string>)[`layers.${index}.geometry_type`]}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={layer.geometry_type} onChange={(event) => updateLayer(index, 'geometry_type', event.target.value)}>{Object.entries(options.geometry_types).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                    <Field label="Status" error={(errors as Record<string, string>)[`layers.${index}.status`]}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={layer.status} onChange={(event) => updateLayer(index, 'status', event.target.value)}>{Object.entries(options.layer_statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                    <Field label="Source URI" error={(errors as Record<string, string>)[`layers.${index}.source_uri`]}><Input value={layer.source_uri} onChange={(event) => updateLayer(index, 'source_uri', event.target.value)} /></Field>
                    <div className="md:col-span-2 xl:col-span-3"><Field label="Analysis notes" error={(errors as Record<string, string>)[`layers.${index}.analysis_notes`]}><textarea className="min-h-20 w-full rounded-md border bg-background px-3 py-2 text-sm" value={layer.analysis_notes} onChange={(event) => updateLayer(index, 'analysis_notes', event.target.value)} /></Field></div>
                    <div className="flex items-end"><Button type="button" variant="ghost" onClick={() => removeLayer(index)}><Trash2 />Remove</Button></div>
                </div>)}
            </section>
            <Button disabled={processing}><Save />Save dataset</Button>
        </form>
    </div></AppLayout>;
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) { return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>; }
