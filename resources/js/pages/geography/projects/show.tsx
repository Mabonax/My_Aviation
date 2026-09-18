import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Save } from 'lucide-react';
import { FormEvent } from 'react';
import { GisProject, GisProjectOptions } from './types';

export default function Show({ project, options }: { project: GisProject; options: GisProjectOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'GIS projects', href: '/gis-projects' }, { title: project.project_code, href: `/gis-projects/${project.id}` }];
    const { data, setData, put, processing, errors } = useForm({ lifecycle_state: project.lifecycle_state });

    function submit(event: FormEvent) {
        event.preventDefault();
        put(`/gis-projects/${project.id}/transition`);
    }

    return <AppLayout breadcrumbs={breadcrumbs}><Head title={project.project_code} /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title={`${project.project_code} / ${project.name}`} description={`${project.project_type.replaceAll('_', ' ')} / ${project.area_name}`} actions={<Button asChild><Link href={`/gis-projects/${project.id}/missions/create`}>Assign Mission<Plus /></Link></Button>} />
        <div className="grid gap-4 xl:grid-cols-2">
            <Panel title="Project"><div className="mb-3"><StatusBadge value={project.lifecycle_state} /></div><Detail label="Client or stakeholder" value={project.client_or_stakeholder} /><Detail label="Responsible role" value={project.responsible_role} /><Detail label="Created by" value={project.created_by} /><Detail label="Updated by" value={project.updated_by} /></Panel>
            <Panel title="Area"><Detail label="Area name" value={project.area_name} /><Detail label="Location search" value={project.location_search_query} /><Detail label="Centroid" value={project.centroid_latitude && project.centroid_longitude ? `${project.centroid_latitude}, ${project.centroid_longitude}` : null} /><Detail label="Boundary points" value={project.area_boundary ? String(project.area_boundary.length) : null} /></Panel>
            <Panel title="Governance"><Detail label="Source reference" value={project.source_reference} /><Detail label="Source version" value={project.source_version} /><Detail label="Evidence required" value={project.evidence_required} /><Detail label="Data governance notes" value={project.data_governance_notes} /></Panel>
            <Panel title="Workflow"><form onSubmit={submit} className="space-y-4"><div><Label>Lifecycle state</Label><select className="mt-2 w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.lifecycle_state} onChange={(event) => setData('lifecycle_state', event.target.value)}>{Object.entries(options.states).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select>{errors.lifecycle_state && <p className="mt-2 text-sm text-destructive">{errors.lifecycle_state}</p>}</div><Button disabled={processing}><Save />Update workflow</Button></form></Panel>
        </div>
        <section className="rounded-lg border bg-card text-card-foreground shadow-xs">
            <h2 className="border-b px-4 py-3 text-base font-semibold">Mapping Missions</h2>
            {project.project_missions.length === 0 ? <p className="px-4 py-4 text-sm text-muted-foreground">No GIS missions assigned.</p> : <div className="divide-y">{project.project_missions.map((assignment) => <div key={assignment.id} className="space-y-4 px-4 py-4"><div className="grid gap-3 lg:grid-cols-[1fr_12rem_10rem]"><div><div className="font-medium">{assignment.mission ? `${assignment.mission.mission_number} / ${assignment.mission.purpose}` : 'Mission unavailable'}</div><p className="mt-1 text-sm text-muted-foreground">{assignment.mapping_objective}</p><p className="mt-1 text-xs text-muted-foreground">{assignment.expected_outputs.map((output) => output.replaceAll('_', ' ')).join(', ')}</p></div><div className="text-sm text-muted-foreground">{assignment.field_verification_required.replaceAll('_', ' ')}</div><StatusBadge value={assignment.status} /></div><div className="flex flex-wrap items-center gap-2"><Button size="sm" variant="outline" asChild><Link href={`/gis-project-missions/${assignment.id}/datasets/create`}>Add Dataset<Plus /></Link></Button><span className="text-xs text-muted-foreground">{assignment.datasets.length} datasets</span></div>{assignment.datasets.length > 0 && <div className="rounded-md border">{assignment.datasets.map((dataset) => <div key={dataset.id} className="border-t first:border-t-0 px-3 py-3"><div className="flex flex-wrap items-center justify-between gap-2"><div><div className="text-sm font-medium">{dataset.dataset_code} / {dataset.title}</div><div className="text-xs text-muted-foreground">{dataset.dataset_type.replaceAll('_', ' ')} / {dataset.storage_uri}</div></div><div className="flex gap-2"><StatusBadge value={dataset.processing_status} /><StatusBadge value={dataset.quality_status} /></div></div>{dataset.layers.length > 0 && <div className="mt-3 space-y-3">{dataset.layers.map((layer) => <div key={layer.id} className="rounded-md border bg-muted/20 p-3"><div className="flex flex-wrap items-center justify-between gap-2"><div><div className="text-sm font-medium">{layer.layer_name}</div><div className="text-xs text-muted-foreground">{layer.layer_type.replaceAll('_', ' ')} / {layer.geometry_type.replaceAll('_', ' ')}</div></div><div className="flex flex-wrap items-center gap-2"><StatusBadge value={layer.status} /><Button size="sm" variant="outline" asChild><Link href={`/gis-layers/${layer.id}/features/create`}>Add Feature<Plus /></Link></Button></div></div>{layer.features.length > 0 && <div className="mt-3 space-y-2">{layer.features.map((feature) => <div key={feature.id} className="rounded-md border bg-background p-3"><div className="flex flex-wrap items-center justify-between gap-2"><div><div className="text-sm font-medium">{feature.feature_code} / {feature.name}</div><div className="text-xs text-muted-foreground">{feature.feature_type.replaceAll('_', ' ')}</div></div><StatusBadge value={feature.verification_status} /></div>{feature.opportunities_findings.length > 0 && <p className="mt-2 text-xs text-muted-foreground">{feature.opportunities_findings.map((record) => `${record.record_type}: ${record.title} (${record.priority})`).join(', ')}</p>}</div>)}</div>}</div>)}</div>}</div>)}</div>}</div>)}</div>}
        </section>
    </div></AppLayout>;
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) { return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><h2 className="mb-4 text-base font-semibold">{title}</h2><div className="space-y-3">{children}</div></section>; }
function Detail({ label, value }: { label: string; value?: string | null }) { return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm break-words">{value || 'Not captured'}</dd></div>; }
