import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { MissionProfile, MissionTrackReport } from '../types';

type TrackForm = {
    source_type: string;
    track_reference: string;
    started_at: string;
    ended_at: string;
    points: Array<{ latitude: number; longitude: number; altitude_ft: number | null; recorded_at: string | null }>;
    anomalies: string[];
    notes: string;
};

export default function CreateTrack({ mission, tracks, sourceTypes }: { mission: MissionProfile; tracks: MissionTrackReport; sourceTypes: Record<string, string> }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Missions', href: '/missions' },
        { title: mission.mission_number, href: `/missions/${mission.id}` },
        { title: 'Flight tracks', href: `/missions/${mission.id}/tracks/create` },
    ];
    const [pointText, setPointText] = useState('-26.0300,28.1200,320\n-26.0310,28.1240,360');
    const [anomalyText, setAnomalyText] = useState('');
    const { data, setData, post, processing, errors, transform } = useForm<TrackForm>({
        source_type: 'manual',
        track_reference: '',
        started_at: '',
        ended_at: '',
        points: [],
        anomalies: [],
        notes: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((values) => ({
            ...values,
            track_reference: values.track_reference || null,
            started_at: values.started_at || null,
            ended_at: values.ended_at || null,
            points: parsePoints(pointText),
            anomalies: anomalyText.split(/\r?\n/).map((line) => line.trim()).filter(Boolean),
            notes: values.notes || null,
        }));
        post(`/missions/${mission.id}/tracks`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Flight tracks - ${mission.mission_number}`} />
            <form onSubmit={submit} className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Record flight track" description={`${mission.mission_number} / ${tracks.summary.total} tracks recorded`} actions={<Button variant="outline" asChild><Link href={`/missions/${mission.id}`}>Back</Link></Button>} />

                <section className="grid gap-4 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-2">
                    <SelectMap label="Source" value={data.source_type} onChange={(value) => setData('source_type', value)} options={sourceTypes} error={errors.source_type} />
                    <Field label="Track reference" error={errors.track_reference}><Input value={data.track_reference} onChange={(event) => setData('track_reference', event.target.value)} /></Field>
                    <Field label="Started at" error={errors.started_at}><Input type="datetime-local" value={data.started_at} onChange={(event) => setData('started_at', event.target.value)} /></Field>
                    <Field label="Ended at" error={errors.ended_at}><Input type="datetime-local" value={data.ended_at} onChange={(event) => setData('ended_at', event.target.value)} /></Field>
                </section>

                <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
                    <Label>Track points</Label>
                    <textarea value={pointText} onChange={(event) => setPointText(event.target.value)} className="mt-2 min-h-40 w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" />
                    <p className="mt-2 text-xs text-muted-foreground">One point per line: latitude, longitude, optional altitude ft, optional timestamp.</p>
                    {errors.points && <p className="mt-2 text-sm text-destructive">{errors.points}</p>}
                </section>

                <section className="grid gap-4 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-2">
                    <Field label="Anomalies" error={errors.anomalies}><textarea value={anomalyText} onChange={(event) => setAnomalyText(event.target.value)} className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" /></Field>
                    <Field label="Notes" error={errors.notes}><textarea value={data.notes} onChange={(event) => setData('notes', event.target.value)} className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" /></Field>
                </section>

                <div className="flex gap-3">
                    <Button disabled={processing}>Save track</Button>
                    <Button variant="outline" asChild><Link href={`/missions/${mission.id}`}>Cancel</Link></Button>
                </div>
            </form>
        </AppLayout>
    );
}

function parsePoints(value: string) {
    return value.split(/\r?\n/).map((line) => {
        const [latitude, longitude, altitude_ft, recorded_at] = line.split(',').map((part) => part?.trim());
        return {
            latitude: Number(latitude),
            longitude: Number(longitude),
            altitude_ft: altitude_ft ? Number(altitude_ft) : null,
            recorded_at: recorded_at || null,
        };
    }).filter((point) => Number.isFinite(point.latitude) && Number.isFinite(point.longitude));
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <div className="space-y-2"><Label>{label}</Label>{children}{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectMap({ label, value, onChange, options, error }: { label: string; value: string; onChange: (value: string) => void; options: Record<string, string>; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(options).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}