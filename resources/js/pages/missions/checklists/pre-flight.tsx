import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { MissionChecklistReport, MissionProfile } from '../types';

type ChecklistForm = {
    results: Record<string, { result: string; notes: string }>;
    exceptions: string;
};

export default function PreFlight({ mission, checklist }: { mission: MissionProfile; checklist: MissionChecklistReport }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Missions', href: '/missions' },
        { title: mission.mission_number, href: `/missions/${mission.id}` },
        { title: 'Pre-flight checklist', href: `/missions/${mission.id}/pre-flight-checklist` },
    ];

    const initialResults = Object.fromEntries((checklist.template?.items ?? []).map((item) => [item.key, { result: checklist.latest?.results[item.key]?.result ?? 'pass', notes: checklist.latest?.results[item.key]?.notes ?? '' }]));
    const { data, setData, post, processing, errors } = useForm<ChecklistForm>({
        results: initialResults,
        exceptions: checklist.latest?.exceptions ?? '',
    });

    function updateResult(key: string, result: string) {
        setData('results', { ...data.results, [key]: { ...data.results[key], result } });
    }

    function updateNotes(key: string, notes: string) {
        setData('results', { ...data.results, [key]: { ...data.results[key], notes } });
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        post(`/missions/${mission.id}/pre-flight-checklist`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Pre-flight checklist - ${mission.mission_number}`} />
            <form onSubmit={submit} className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Pre-flight checklist" description={`${mission.mission_number} / ${checklist.template?.version ?? 'No active template'}`} actions={<Button variant="outline" asChild><Link href={`/missions/${mission.id}`}>Back</Link></Button>} />

                {!checklist.template ? (
                    <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
                        <p className="text-sm text-muted-foreground">No active pre-flight checklist template is configured.</p>
                    </section>
                ) : (
                    <>
                        <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
                            <div className="grid gap-2 text-sm md:grid-cols-3">
                                <Detail label="Template" value={checklist.template.name} />
                                <Detail label="Version" value={checklist.template.version} />
                                <Detail label="Effective" value={checklist.template.effective_date} />
                            </div>
                        </section>

                        <section className="rounded-lg border bg-card text-card-foreground shadow-xs">
                            {(checklist.template.items ?? []).map((item) => (
                                <div key={item.key} className="grid gap-3 border-t p-4 first:border-t-0 md:grid-cols-[minmax(0,1fr)_180px_minmax(0,1fr)] md:items-start">
                                    <div>
                                        <Label>{item.sequence}. {item.label}</Label>
                                        <p className="mt-1 text-xs uppercase text-muted-foreground">{item.required ? 'Required' : 'Optional'}</p>
                                    </div>
                                    <Select value={data.results[item.key]?.result ?? 'pass'} onValueChange={(value) => updateResult(item.key, value)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pass">Pass</SelectItem>
                                            <SelectItem value="fail">Fail</SelectItem>
                                            <SelectItem value="not_applicable">N/A</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <textarea value={data.results[item.key]?.notes ?? ''} onChange={(event) => updateNotes(item.key, event.target.value)} placeholder="Notes" className="min-h-12 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" />
                                </div>
                            ))}
                        </section>

                        <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
                            <Label>Exceptions</Label>
                            <textarea value={data.exceptions} onChange={(event) => setData('exceptions', event.target.value)} className="mt-2 min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" />
                            {errors.results && <p className="mt-2 text-sm text-destructive">{errors.results}</p>}
                            {errors.exceptions && <p className="mt-2 text-sm text-destructive">{errors.exceptions}</p>}
                        </section>

                        <div className="flex gap-3">
                            <Button disabled={processing}>Save checklist</Button>
                            <Button variant="outline" asChild><Link href={`/missions/${mission.id}`}>Cancel</Link></Button>
                        </div>
                    </>
                )}
            </form>
        </AppLayout>
    );
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1">{value || 'Not captured'}</dd></div>;
}