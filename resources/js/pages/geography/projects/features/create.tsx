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
import { GisFeatureOptions } from '../types';

type SpatialLayerContext = {
    id: number;
    layer_name: string;
    layer_type: string;
    geometry_type: string;
    dataset: { id: number; dataset_code: string; title: string };
    project: { id: number; project_code: string; name: string };
    mission: { id: number; mission_number: string; purpose: string };
};

type OpportunityFindingFormData = {
    record_type: string;
    category: string;
    title: string;
    description: string;
    significance: string;
    recommended_action: string;
    priority: string;
    status: string;
    evidence_reference: string;
    due_date: string;
    responsible_role: string;
};

type FeatureFormData = {
    feature_code: string;
    name: string;
    feature_type: string;
    geometry_reference: string;
    confidence_score: string;
    verification_status: string;
    interpretation_notes: string;
    evidence_notes: string;
    opportunities_findings: OpportunityFindingFormData[];
};

export default function Create({ spatialLayer, options }: { spatialLayer: SpatialLayerContext; options: GisFeatureOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'GIS projects', href: '/gis-projects' }, { title: spatialLayer.project.project_code, href: `/gis-projects/${spatialLayer.project.id}` }, { title: 'Feature', href: `/gis-layers/${spatialLayer.id}/features/create` }];
    const { data, setData, post, processing, errors, transform } = useForm<FeatureFormData>({
        feature_code: '',
        name: '',
        feature_type: 'environmental_feature',
        geometry_reference: '',
        confidence_score: '',
        verification_status: 'unverified',
        interpretation_notes: '',
        evidence_notes: '',
        opportunities_findings: [],
    });

    function addRecord() {
        setData('opportunities_findings', [...data.opportunities_findings, { record_type: 'finding', category: 'environmental', title: '', description: '', significance: 'medium', recommended_action: '', priority: 'routine', status: 'draft', evidence_reference: '', due_date: '', responsible_role: '' }]);
    }

    function updateRecord(index: number, key: keyof OpportunityFindingFormData, value: string) {
        setData('opportunities_findings', data.opportunities_findings.map((record, current) => current === index ? { ...record, [key]: value } : record));
    }

    function removeRecord(index: number) {
        setData('opportunities_findings', data.opportunities_findings.filter((_, current) => current !== index));
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            confidence_score: payload.confidence_score || null,
            evidence_notes: payload.evidence_notes || null,
            opportunities_findings: payload.opportunities_findings.map((record) => ({
                ...record,
                recommended_action: record.recommended_action || null,
                evidence_reference: record.evidence_reference || null,
                due_date: record.due_date || null,
                responsible_role: record.responsible_role || null,
            })),
        }));
        post(`/gis-layers/${spatialLayer.id}/features`);
    }

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Capture GIS feature" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Capture GIS feature" description={`${spatialLayer.layer_name} / ${spatialLayer.dataset.dataset_code}`} />
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Feature code" error={errors.feature_code}><Input value={data.feature_code} onChange={(event) => setData('feature_code', event.target.value)} /></Field>
                <Field label="Name" error={errors.name}><Input value={data.name} onChange={(event) => setData('name', event.target.value)} /></Field>
                <Field label="Feature type" error={errors.feature_type}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.feature_type} onChange={(event) => setData('feature_type', event.target.value)}>{Object.entries(options.feature_types).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                <Field label="Confidence score" error={errors.confidence_score}><Input value={data.confidence_score} onChange={(event) => setData('confidence_score', event.target.value)} /></Field>
                <Field label="Verification" error={errors.verification_status}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.verification_status} onChange={(event) => setData('verification_status', event.target.value)}>{Object.entries(options.verification_statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                <Field label="Layer context"><Input value={`${spatialLayer.layer_type.replaceAll('_', ' ')} / ${spatialLayer.geometry_type}`} readOnly /></Field>
            </section>
            <Field label="Geometry reference" error={errors.geometry_reference}><textarea className="min-h-28 w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.geometry_reference} onChange={(event) => setData('geometry_reference', event.target.value)} /></Field>
            <Field label="Interpretation notes" error={errors.interpretation_notes}><textarea className="min-h-32 w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.interpretation_notes} onChange={(event) => setData('interpretation_notes', event.target.value)} /></Field>
            <Field label="Evidence notes" error={errors.evidence_notes}><textarea className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.evidence_notes} onChange={(event) => setData('evidence_notes', event.target.value)} /></Field>
            <section className="space-y-4">
                <div className="flex items-center justify-between gap-3"><h2 className="text-base font-semibold">Opportunities and Findings</h2><Button type="button" variant="outline" onClick={addRecord}>Add Record<Plus /></Button></div>
                {data.opportunities_findings.map((record, index) => <div key={index} className="grid gap-4 rounded-lg border p-4 md:grid-cols-2 xl:grid-cols-4">
                    <Field label="Type" error={(errors as Record<string, string>)[`opportunities_findings.${index}.record_type`]}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={record.record_type} onChange={(event) => updateRecord(index, 'record_type', event.target.value)}>{Object.entries(options.record_types).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                    <Field label="Category" error={(errors as Record<string, string>)[`opportunities_findings.${index}.category`]}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={record.category} onChange={(event) => updateRecord(index, 'category', event.target.value)}>{Object.entries(options.categories).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                    <Field label="Title" error={(errors as Record<string, string>)[`opportunities_findings.${index}.title`]}><Input value={record.title} onChange={(event) => updateRecord(index, 'title', event.target.value)} /></Field>
                    <Field label="Significance" error={(errors as Record<string, string>)[`opportunities_findings.${index}.significance`]}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={record.significance} onChange={(event) => updateRecord(index, 'significance', event.target.value)}>{Object.entries(options.significance).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                    <Field label="Priority" error={(errors as Record<string, string>)[`opportunities_findings.${index}.priority`]}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={record.priority} onChange={(event) => updateRecord(index, 'priority', event.target.value)}>{Object.entries(options.priorities).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                    <Field label="Status" error={(errors as Record<string, string>)[`opportunities_findings.${index}.status`]}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={record.status} onChange={(event) => updateRecord(index, 'status', event.target.value)}>{Object.entries(options.statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                    <Field label="Due date" error={(errors as Record<string, string>)[`opportunities_findings.${index}.due_date`]}><Input type="date" value={record.due_date} onChange={(event) => updateRecord(index, 'due_date', event.target.value)} /></Field>
                    <Field label="Responsible role" error={(errors as Record<string, string>)[`opportunities_findings.${index}.responsible_role`]}><Input value={record.responsible_role} onChange={(event) => updateRecord(index, 'responsible_role', event.target.value)} /></Field>
                    <div className="md:col-span-2"><Field label="Description" error={(errors as Record<string, string>)[`opportunities_findings.${index}.description`]}><textarea className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" value={record.description} onChange={(event) => updateRecord(index, 'description', event.target.value)} /></Field></div>
                    <div className="md:col-span-2"><Field label="Recommended action" error={(errors as Record<string, string>)[`opportunities_findings.${index}.recommended_action`]}><textarea className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" value={record.recommended_action} onChange={(event) => updateRecord(index, 'recommended_action', event.target.value)} /></Field></div>
                    <div className="md:col-span-2 xl:col-span-3"><Field label="Evidence reference" error={(errors as Record<string, string>)[`opportunities_findings.${index}.evidence_reference`]}><Input value={record.evidence_reference} onChange={(event) => updateRecord(index, 'evidence_reference', event.target.value)} /></Field></div>
                    <div className="flex items-end"><Button type="button" variant="ghost" onClick={() => removeRecord(index)}><Trash2 />Remove</Button></div>
                </div>)}
            </section>
            <Button disabled={processing}><Save />Save feature</Button>
        </form>
    </div></AppLayout>;
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) { return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>; }
