import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { MissionCrewReport, MissionProfile } from '../types';

type Option = { id: number; label: string; email?: string | null };
type CrewOptions = {
    pilots: Option[];
    users: Option[];
    crew_roles: Record<string, string>;
    briefing_statuses: Record<string, string>;
    competency_statuses: Record<string, string>;
    acceptance_statuses: Record<string, string>;
};

type CrewForm = Record<string, string>;

export default function CreateCrew({ mission, crew, options }: { mission: MissionProfile; crew: MissionCrewReport; options: CrewOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Missions', href: '/missions' },
        { title: mission.mission_number, href: `/missions/${mission.id}` },
        { title: 'Crew', href: `/missions/${mission.id}/crew/create` },
    ];

    const { data, setData, post, processing, errors, transform } = useForm<CrewForm>({
        crew_role: 'observer',
        display_name: '',
        email: '',
        phone: '',
        uas_pilot_id: '',
        user_id: '',
        briefing_status: 'pending',
        competency_status: 'not_checked',
        acceptance_status: 'pending',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        notes: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((values) => ({
            ...values,
            uas_pilot_id: values.uas_pilot_id || null,
            user_id: values.user_id || null,
            email: values.email || null,
            phone: values.phone || null,
            emergency_contact_name: values.emergency_contact_name || null,
            emergency_contact_phone: values.emergency_contact_phone || null,
            notes: values.notes || null,
        }));
        post(`/missions/${mission.id}/crew`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Crew - ${mission.mission_number}`} />
            <form onSubmit={submit} className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Assign mission crew" description={`${mission.mission_number} / ${crew.summary.total} assigned`} actions={<Button variant="outline" asChild><Link href={`/missions/${mission.id}`}>Back</Link></Button>} />

                <section className="grid gap-4 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-2">
                    <SelectMap label="Crew role" value={data.crew_role} onChange={(value) => setData('crew_role', value)} options={options.crew_roles} error={errors.crew_role} />
                    <Field label="Display name" error={errors.display_name}><Input value={data.display_name} onChange={(event) => setData('display_name', event.target.value)} /></Field>
                    <Field label="Email" error={errors.email}><Input value={data.email} onChange={(event) => setData('email', event.target.value)} /></Field>
                    <Field label="Phone" error={errors.phone}><Input value={data.phone} onChange={(event) => setData('phone', event.target.value)} /></Field>
                    <SelectOptions label="Linked pilot" value={data.uas_pilot_id} onChange={(value) => setData('uas_pilot_id', value)} options={options.pilots} placeholder="No linked pilot" error={errors.uas_pilot_id} />
                    <SelectOptions label="Linked user" value={data.user_id} onChange={(value) => setData('user_id', value)} options={options.users} placeholder="No linked user" error={errors.user_id} />
                    <SelectMap label="Briefing" value={data.briefing_status} onChange={(value) => setData('briefing_status', value)} options={options.briefing_statuses} error={errors.briefing_status} />
                    <SelectMap label="Competency" value={data.competency_status} onChange={(value) => setData('competency_status', value)} options={options.competency_statuses} error={errors.competency_status} />
                    <SelectMap label="Acceptance" value={data.acceptance_status} onChange={(value) => setData('acceptance_status', value)} options={options.acceptance_statuses} error={errors.acceptance_status} />
                    <Field label="Emergency contact" error={errors.emergency_contact_name}><Input value={data.emergency_contact_name} onChange={(event) => setData('emergency_contact_name', event.target.value)} /></Field>
                    <Field label="Emergency contact phone" error={errors.emergency_contact_phone}><Input value={data.emergency_contact_phone} onChange={(event) => setData('emergency_contact_phone', event.target.value)} /></Field>
                    <Field label="Notes" error={errors.notes}><textarea value={data.notes} onChange={(event) => setData('notes', event.target.value)} className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" /></Field>
                </section>

                <div className="flex gap-3">
                    <Button disabled={processing}>Assign crew</Button>
                    <Button variant="outline" asChild><Link href={`/missions/${mission.id}`}>Cancel</Link></Button>
                </div>
            </form>
        </AppLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <div className="space-y-2"><Label>{label}</Label>{children}{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectMap({ label, value, onChange, options, error }: { label: string; value: string; onChange: (value: string) => void; options: Record<string, string>; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(options).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectOptions({ label, value, onChange, options, placeholder, error }: { label: string; value: string; onChange: (value: string) => void; options: Option[]; placeholder: string; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue placeholder={placeholder} /></SelectTrigger><SelectContent>{options.map((option) => <SelectItem key={option.id} value={option.id.toString()}>{option.label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}