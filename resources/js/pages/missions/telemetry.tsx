import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useRef, type FormEvent } from 'react';

type Mission = { id: number; mission_number: string; pilot: string; aircraft: string | null; serial: string | null; can_import: boolean };
type FlightImport = {
    id: number; state: string; content_sha256: string; accepted_at: string | null;
    flight: { aircraft_serial: string; actual_takeoff_at: string; actual_landing_at: string; point_count: number };
};
type Page = { data: FlightImport[]; prev_page_url: string | null; next_page_url: string | null };

export default function Telemetry({ mission, imports }: { mission: Mission; imports: Page }) {
    const upload = useForm<{ file: File | null }>({ file: null });
    const fileInput = useRef<HTMLInputElement>(null);
    const base = `/missions/${mission.id}/telemetry`;
    function submit(event: FormEvent) {
        event.preventDefault();
        upload.post(base, { forceFormData: true, preserveScroll: true, onSuccess: () => {
            upload.reset();
            if (fileInput.current) fileInput.current.value = '';
        } });
    }
    return (
        <AppLayout breadcrumbs={[{ title: 'Missions', href: '/missions' }, { title: mission.mission_number, href: `/missions/${mission.id}` }, { title: 'Telemetry', href: base }]}>
            <Head title={`Telemetry · ${mission.mission_number}`} />
            <main className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Flight telemetry" description={mission.mission_number} actions={<Button variant="outline" asChild><Link href={`/missions/${mission.id}`}>Back to mission</Link></Button>} />
                <section className="rounded-xl border p-5">
                    <h2 className="font-semibold">Mission assignment</h2>
                    <p className="mt-2 text-sm">Pilot: {mission.pilot.trim() || 'Unassigned'} · Aircraft: {mission.aircraft ?? 'Unassigned'} · Serial: {mission.serial ?? 'Missing'}</p>
                </section>
                <section className="rounded-xl border p-5">
                    <h2 className="font-semibold">Upload flight evidence</h2>
                    <p className="mt-2 text-sm text-muted-foreground">Upload a YAW CSV v1 file up to 2 MiB. The flight must begin with takeoff, end with landing and match this aircraft serial. Uploading stages evidence for review; acceptance updates the flight track, pilot log and aircraft folio.</p>
                    <p className="mt-2 text-sm text-muted-foreground">Native DJI and Autel logs are not supported yet. Times need an explicit timezone; altitude is in feet.</p>
                    <code className="mt-3 block overflow-x-auto rounded bg-muted p-3 text-xs">recorded_at,latitude,longitude,altitude_ft,aircraft_serial,event</code>
                    {!mission.can_import ? <p className="mt-4 text-sm">Import is available to authorised managers for completed missions awaiting post-flight propagation.</p> : (
                        <form onSubmit={submit} className="mt-4 flex flex-col gap-3">
                            <Label htmlFor="telemetry-file">Flight CSV</Label>
                            <Input id="telemetry-file" ref={fileInput} type="file" accept=".csv,text/csv" disabled={upload.processing} onChange={(event) => upload.setData('file', event.target.files?.[0] ?? null)} />
                            {Object.entries(upload.errors).map(([key, message]) => <InputError key={key} message={message} />)}
                            {upload.progress && <p role="status">Uploading: {upload.progress.percentage}%</p>}
                            <Button type="submit" disabled={upload.processing || !upload.data.file}>{upload.processing ? 'Uploading…' : 'Upload for review'}</Button>
                        </form>
                    )}
                </section>
                <section className="flex flex-col gap-4" aria-label="Imported flights">
                    <h2 className="font-semibold">Imported flight evidence</h2>
                    {imports.data.length === 0 && <p className="text-sm text-muted-foreground">No flight evidence has been imported.</p>}
                    {imports.data.map((item) => <Review key={item.id} item={item} mission={mission} />)}
                    <nav aria-label="Import history pages" className="flex gap-4">
                        {imports.prev_page_url && <Link href={imports.prev_page_url}>Previous</Link>}
                        {imports.next_page_url && <Link href={imports.next_page_url}>Next</Link>}
                    </nav>
                </section>
            </main>
        </AppLayout>
    );
}

function Review({ item, mission }: { item: FlightImport; mission: Mission }) {
    const form = useForm({
        telemetry_confirmed: false, pilot_confirmed: false, aircraft_confirmed: false,
        defects_declared: '', occurrence_declared: '', closure_notes: '',
    });
    const duration = (Date.parse(item.flight.actual_landing_at) - Date.parse(item.flight.actual_takeoff_at)) / 60000;
    const ready = form.data.telemetry_confirmed && form.data.pilot_confirmed && form.data.aircraft_confirmed
        && form.data.defects_declared !== '' && form.data.occurrence_declared !== '';
    function accept(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({ ...data, defects_declared: data.defects_declared === 'yes', occurrence_declared: data.occurrence_declared === 'yes' }))
            .post(`/missions/${mission.id}/telemetry/${item.id}/accept`, { preserveScroll: true });
    }
    return (
        <article className="rounded-xl border p-5">
            <h3 className="font-semibold">Import #{item.id} · {item.state === 'accepted' ? 'Accepted' : 'Awaiting review'}</h3>
            <dl className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                <div><dt className="text-muted-foreground">Aircraft serial</dt><dd>{item.flight.aircraft_serial}</dd></div>
                <div><dt className="text-muted-foreground">Recorded duration</dt><dd>{duration.toFixed(2)} minutes · {item.flight.point_count} samples</dd></div>
                <div><dt className="text-muted-foreground">Takeoff (UTC)</dt><dd>{new Date(item.flight.actual_takeoff_at).toISOString()}</dd></div>
                <div><dt className="text-muted-foreground">Landing (UTC)</dt><dd>{new Date(item.flight.actual_landing_at).toISOString()}</dd></div>
            </dl>
            <details className="mt-3 text-xs"><summary>Evidence fingerprint</summary><p className="mt-2 break-all">{item.content_sha256}</p></details>
            {item.state === 'accepted' ? <p role="status" className="mt-4 text-sm">Accepted {item.accepted_at}. Operational records have been updated.</p> : mission.can_import && (
                <form onSubmit={accept} className="mt-5 flex flex-col gap-4">
                    <p className="text-sm">Confirm the flight details and declarations. Existing checklist and close-out requirements apply.</p>
                    {(['telemetry_confirmed', 'pilot_confirmed', 'aircraft_confirmed'] as const).map((key) => (
                        <label key={key} className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={form.data[key]} disabled={form.processing} onChange={(event) => form.setData(key, event.target.checked)} />
                            {key === 'telemetry_confirmed' ? 'I reviewed the recorded flight and times' : key === 'pilot_confirmed' ? `I confirm the assigned pilot: ${mission.pilot}` : `I confirm the assigned aircraft: ${mission.aircraft}`}
                        </label>
                    ))}
                    {(['defects_declared', 'occurrence_declared'] as const).map((key) => (
                        <label key={key} className="flex flex-col gap-2 text-sm">
                            {key === 'defects_declared' ? 'Were defects identified?' : 'Was an occurrence identified?'}
                            <select required className="rounded-md border bg-background p-2" value={form.data[key]} disabled={form.processing} onChange={(event) => form.setData(key, event.target.value)}>
                                <option value="">Select a declaration</option><option value="no">No</option><option value="yes">Yes</option>
                            </select>
                        </label>
                    ))}
                    <label className="flex flex-col gap-2 text-sm">Closure notes<textarea className="rounded-md border bg-background p-2" maxLength={2000} value={form.data.closure_notes} disabled={form.processing} onChange={(event) => form.setData('closure_notes', event.target.value)} /></label>
                    <div role="alert">{Object.entries(form.errors).map(([key, message]) => <InputError key={key} message={message} />)}</div>
                    <Button type="submit" disabled={form.processing || !ready}>{form.processing ? 'Accepting…' : 'Accept and update operational records'}</Button>
                </form>
            )}
        </article>
    );
}
