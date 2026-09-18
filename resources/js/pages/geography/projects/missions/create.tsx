import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { GisProjectListItem, GisProjectMissionOptions } from '../types';

type AssignmentFormData = {
    uas_mission_id: string | number;
    mapping_objective: string;
    capture_plan: string;
    expected_outputs: string[];
    field_verification_required: string;
    evidence_notes: string | null;
    status: string;
};

export default function Create({ project, options }: { project: GisProjectListItem; options: GisProjectMissionOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'GIS projects', href: '/gis-projects' }, { title: project.project_code, href: `/gis-projects/${project.id}` }, { title: 'Assign mission', href: `/gis-projects/${project.id}/missions/create` }];
    const { data, setData, post, processing, errors, transform } = useForm<AssignmentFormData>({
        uas_mission_id: '',
        mapping_objective: '',
        capture_plan: '',
        expected_outputs: ['imagery'],
        field_verification_required: 'required',
        evidence_notes: '',
        status: 'planned',
    });

    function toggleOutput(value: string, checked: boolean) {
        setData('expected_outputs', checked ? [...data.expected_outputs, value] : data.expected_outputs.filter((output) => output !== value));
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({ ...payload, uas_mission_id: Number(payload.uas_mission_id), evidence_notes: payload.evidence_notes || null }));
        post(`/gis-projects/${project.id}/missions`);
    }

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Assign GIS mission" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Assign GIS mission" description={`${project.project_code} / ${project.name}`} />
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2">
                <Field label="Mission" error={errors.uas_mission_id}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.uas_mission_id} onChange={(event) => setData('uas_mission_id', event.target.value)}><option value="">Select mission</option>{options.missions.map((mission) => <option key={mission.id} value={mission.id}>{mission.mission_number} / {mission.purpose} / {mission.release_gate_state}</option>)}</select></Field>
                <Field label="Mapping objective" error={errors.mapping_objective}><input className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.mapping_objective} onChange={(event) => setData('mapping_objective', event.target.value)} /></Field>
                <Field label="Field verification" error={errors.field_verification_required}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.field_verification_required} onChange={(event) => setData('field_verification_required', event.target.value)}>{Object.entries(options.field_verification).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
                <Field label="Status" error={errors.status}><select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.status} onChange={(event) => setData('status', event.target.value)}>{Object.entries(options.statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
            </section>
            <Field label="Expected outputs" error={errors.expected_outputs}><div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">{Object.entries(options.outputs).map(([value, label]) => <label key={value} className="flex items-center gap-3 rounded-lg border p-3 text-sm"><Checkbox checked={data.expected_outputs.includes(value)} onCheckedChange={(checked) => toggleOutput(value, checked === true)} />{label}</label>)}</div></Field>
            <Field label="Capture plan" error={errors.capture_plan}><textarea className="min-h-32 w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.capture_plan} onChange={(event) => setData('capture_plan', event.target.value)} /></Field>
            <Field label="Evidence notes" error={errors.evidence_notes}><textarea className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" value={data.evidence_notes || ''} onChange={(event) => setData('evidence_notes', event.target.value)} /></Field>
            <Button disabled={processing || options.missions.length === 0}><Save />Assign mission</Button>
        </form>
    </div></AppLayout>;
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) { return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>; }
