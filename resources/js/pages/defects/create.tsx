import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { MissionProfile } from '../missions/types';

type AircraftOption = { id: number; label: string; operational_status: string };

type DefectForm = {
    uas_aircraft_id: string;
    source: string;
    severity: string;
    title: string;
    description: string;
    immediate_action: string;
    reported_at: string;
    evidence_references: string[];
};

export default function Create({ mission, aircraft, sources, severities }: { mission: MissionProfile | null; aircraft: AircraftOption[]; sources: Record<string, string>; severities: Record<string, string> }) {
    const [evidenceText, setEvidenceText] = useState('');
    const breadcrumbs: BreadcrumbItem[] = mission ? [
        { title: 'Missions', href: '/missions' },
        { title: mission.mission_number, href: `/missions/${mission.id}` },
        { title: 'Report defect', href: `/missions/${mission.id}/defects/create` },
    ] : [
        { title: 'Defects', href: '/defects' },
        { title: 'Create', href: '/defects/create' },
    ];
    const { data, setData, post, processing, errors, transform } = useForm<DefectForm>({
        uas_aircraft_id: mission?.aircraft?.id?.toString() ?? '',
        source: mission ? 'post_flight' : 'inspection',
        severity: 'observation',
        title: '',
        description: '',
        immediate_action: '',
        reported_at: '',
        evidence_references: [],
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((values) => ({
            ...values,
            uas_aircraft_id: values.uas_aircraft_id || null,
            immediate_action: values.immediate_action || null,
            reported_at: values.reported_at || null,
            evidence_references: evidenceText.split(/\r?\n/).map((line) => line.trim()).filter(Boolean),
        }));
        post(mission ? `/missions/${mission.id}/defects` : '/defects');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Report defect" />
            <form onSubmit={submit} className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Report defect" description={mission ? `${mission.mission_number} / ${mission.aircraft?.registration ?? 'no aircraft assigned'}` : 'Capture aircraft defect source, severity and serviceability impact.'} actions={<Button variant="outline" asChild><Link href={mission ? `/missions/${mission.id}` : '/defects'}>Back</Link></Button>} />

                <section className="grid gap-4 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-2">
                    <SelectField label="Aircraft" value={data.uas_aircraft_id} onChange={(value) => setData('uas_aircraft_id', value)} options={aircraft} placeholder="Select aircraft" disabled={Boolean(mission?.aircraft)} error={errors.uas_aircraft_id} />
                    <SelectMap label="Source" value={data.source} onChange={(value) => setData('source', value)} options={sources} error={errors.source} />
                    <SelectMap label="Severity" value={data.severity} onChange={(value) => setData('severity', value)} options={severities} error={errors.severity} />
                    <Field label="Reported at" error={errors.reported_at}><Input type="datetime-local" value={data.reported_at} onChange={(event) => setData('reported_at', event.target.value)} /></Field>
                    <Field label="Title" error={errors.title}><Input value={data.title} onChange={(event) => setData('title', event.target.value)} /></Field>
                </section>

                <section className="grid gap-4 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-2">
                    <Field label="Description" error={errors.description}><TextArea value={data.description} onChange={(event) => setData('description', event.target.value)} /></Field>
                    <Field label="Immediate action" error={errors.immediate_action}><TextArea value={data.immediate_action} onChange={(event) => setData('immediate_action', event.target.value)} /></Field>
                    <Field label="Evidence references" error={errors.evidence_references}><TextArea value={evidenceText} onChange={(event) => setEvidenceText(event.target.value)} /></Field>
                </section>

                <div className="flex gap-3">
                    <Button disabled={processing}>Report defect</Button>
                    <Button variant="outline" asChild><Link href={mission ? `/missions/${mission.id}` : '/defects'}>Cancel</Link></Button>
                </div>
            </form>
        </AppLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <div className="space-y-2"><Label>{label}</Label>{children}{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectField({ label, value, onChange, options, placeholder, disabled, error }: { label: string; value: string; onChange: (value: string) => void; options: AircraftOption[]; placeholder: string; disabled?: boolean; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange} disabled={disabled}><SelectTrigger><SelectValue placeholder={placeholder} /></SelectTrigger><SelectContent>{options.map((option) => <SelectItem key={option.id} value={option.id.toString()}>{option.label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectMap({ label, value, onChange, options, error }: { label: string; value: string; onChange: (value: string) => void; options: Record<string, string>; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(options).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function TextArea({ value, onChange }: { value: string; onChange: (event: React.ChangeEvent<HTMLTextAreaElement>) => void }) {
    return <textarea value={value} onChange={onChange} className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm" />;
}