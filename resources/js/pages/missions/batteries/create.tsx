import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { MissionBatteryReport, MissionProfile } from '../types';

type BatteryUsageForm = {
    uas_battery_id: string;
    cycles_added: string;
    state_of_charge_start: string;
    state_of_charge_end: string;
    used_at: string;
    notes: string;
};

export default function Create({ mission, batteries }: { mission: MissionProfile; batteries: MissionBatteryReport }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Missions', href: '/missions' },
        { title: mission.mission_number, href: `/missions/${mission.id}` },
        { title: 'Batteries', href: `/missions/${mission.id}/batteries/create` },
    ];
    const { data, setData, post, processing, errors, transform } = useForm<BatteryUsageForm>({
        uas_battery_id: '',
        cycles_added: '1',
        state_of_charge_start: '',
        state_of_charge_end: '',
        used_at: '',
        notes: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((values) => ({
            ...values,
            cycles_added: values.cycles_added || 1,
            state_of_charge_start: values.state_of_charge_start || null,
            state_of_charge_end: values.state_of_charge_end || null,
            used_at: values.used_at || null,
            notes: values.notes || null,
        }));
        post(`/missions/${mission.id}/batteries`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Mission batteries - ${mission.mission_number}`} />
            <form onSubmit={submit} className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Record mission battery use" description={`${mission.mission_number} / ${batteries.summary.total_batteries} batteries recorded`} actions={<Button variant="outline" asChild><Link href={`/missions/${mission.id}`}>Back</Link></Button>} />

                <section className="grid gap-4 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-2">
                    <SelectField label="Battery" value={data.uas_battery_id} onChange={(value) => setData('uas_battery_id', value)} options={batteries.available_batteries} placeholder="Select battery" error={errors.uas_battery_id} />
                    <Field label="Cycles added" error={errors.cycles_added}><Input value={data.cycles_added} onChange={(event) => setData('cycles_added', event.target.value)} /></Field>
                    <Field label="State of charge start %" error={errors.state_of_charge_start}><Input value={data.state_of_charge_start} onChange={(event) => setData('state_of_charge_start', event.target.value)} /></Field>
                    <Field label="State of charge end %" error={errors.state_of_charge_end}><Input value={data.state_of_charge_end} onChange={(event) => setData('state_of_charge_end', event.target.value)} /></Field>
                    <Field label="Used at" error={errors.used_at}><Input type="datetime-local" value={data.used_at} onChange={(event) => setData('used_at', event.target.value)} /></Field>
                    <Field label="Notes" error={errors.notes}><TextArea value={data.notes} onChange={(event) => setData('notes', event.target.value)} /></Field>
                </section>

                <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
                    <div className="mb-4 grid gap-2 text-sm sm:grid-cols-3">
                        <Summary label="Available" value={batteries.available_batteries.length.toString()} />
                        <Summary label="Recorded" value={batteries.summary.total_batteries.toString()} />
                        <Summary label="Attention" value={batteries.summary.attention_required.toString()} />
                    </div>
                    <div className="grid gap-2 md:grid-cols-2">
                        {batteries.available_batteries.map((battery) => (
                            <div key={battery.id} className="rounded-md border p-3 text-sm">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="font-medium">{battery.label}</span>
                                    <StatusBadge value={battery.health_status.replaceAll('_', ' ')} />
                                </div>
                                <p className="mt-1 text-muted-foreground">{battery.cycle_count}{battery.maximum_cycles ? ` / ${battery.maximum_cycles}` : ''} cycles</p>
                            </div>
                        ))}
                    </div>
                </section>

                <div className="flex gap-3">
                    <Button disabled={processing}>Record battery use</Button>
                    <Button variant="outline" asChild><Link href={`/missions/${mission.id}`}>Cancel</Link></Button>
                </div>
            </form>
        </AppLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <div className="space-y-2"><Label>{label}</Label>{children}{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectField({ label, value, onChange, options, placeholder, error }: { label: string; value: string; onChange: (value: string) => void; options: MissionBatteryReport['available_batteries']; placeholder: string; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue placeholder={placeholder} /></SelectTrigger><SelectContent>{options.map((option) => <SelectItem key={option.id} value={option.id.toString()}>{option.label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function Summary({ label, value }: { label: string; value: string }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{value}</dd></div>;
}

function TextArea({ value, onChange }: { value: string; onChange: (event: React.ChangeEvent<HTMLTextAreaElement>) => void }) {
    return <textarea value={value} onChange={onChange} className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm" />;
}