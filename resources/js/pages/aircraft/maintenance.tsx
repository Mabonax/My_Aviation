import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';
import ComponentLifecycleCard, { type TrackedComponent } from './component-lifecycle-card';
import MaintenanceAuthorityPanel, { type Authority, type AuthorityMember } from './maintenance-authority-panel';

type Component = TrackedComponent;
type Task = { id: number; title: string; requirement_source: string; due_at: string | null; due_hours: string | null; due_cycles: number | null; interval_days: number | null; interval_hours: string | null; interval_cycles: number | null; previous_task_id: number | null; uas_aircraft_component_id: number | null; completed_at: string | null; completion_evidence: Record<string, string> | null; due_state: { status: string; remaining_days: number | null; remaining_hours: number | null; remaining_cycles: number | null } };
type Props = { aircraft: { id: number; registration: string; operational_status: string }; components: Component[]; tasks: { data: Task[]; prev_page_url: string | null; next_page_url: string | null }; summary: { status: string; blocking_reasons: string[]; review_reasons: string[] }; can_manage: boolean; can_certify: boolean; can_return_to_service: boolean; maintenance_releases: { id: number; maintenance_task_id: number; released_at: string; released_by: number }[]; authorities: Authority[]; authority_members: AuthorityMember[] };

export default function Maintenance({ aircraft, components, tasks, summary, can_manage, can_certify, can_return_to_service, maintenance_releases, authorities, authority_members }: Props) {
    const base = `/aircraft/${aircraft.id}/maintenance`;
    const form = useForm({ title: '', requirement_source: '', uas_aircraft_component_id: '', due_at: '', due_hours: '', due_cycles: '', interval_days: '', interval_hours: '', interval_cycles: '' });
    function schedule(event: FormEvent) {
        event.preventDefault();
        form.post(base, { preserveScroll: true, onSuccess: () => form.reset() });
    }
    return (
        <AppLayout breadcrumbs={[{ title: 'Aircraft', href: '/aircraft' }, { title: aircraft.registration, href: `/aircraft/${aircraft.id}` }, { title: 'Maintenance', href: base }]}>
            <Head title={`Maintenance · ${aircraft.registration}`} />
            <main className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Maintenance programme" description={aircraft.registration} actions={<Button variant="outline" asChild><Link href={`/aircraft/${aircraft.id}`}>Back to aircraft</Link></Button>} />
                <section className="rounded-xl border p-5" aria-label="Maintenance readiness">
                    <h2 className="font-semibold">Maintenance readiness: {summary.status === 'red' ? 'Blocked' : summary.status === 'amber' ? 'Review required' : 'No configured limits due'}</h2>
                    <p className="mt-2 text-sm">Aircraft operational status: {aircraft.operational_status.replaceAll('_', ' ')}. Flight release also checks this status.</p>
                    {[...summary.blocking_reasons, ...summary.review_reasons].map((reason, index) => <p key={index} className="mt-2 text-sm">{reason}</p>)}
                    <p className="mt-2 text-sm text-muted-foreground">Readiness checks all configured aircraft obligations. This workspace shows tasks belonging to the selected operator. Dates become due at the start of the stated day. Hours and cycles are absolute component totals; a cycle is one accepted mission flight.</p>
                </section>
                <section className="rounded-xl border p-5">
                    <h2 className="font-semibold">Component usage and life limits</h2>
                    <p className="mt-2 text-sm text-muted-foreground">Recorded flights update active components installed before takeoff. Existing usage is retained; historical flights are not backfilled automatically.</p>
                    {components.length === 0 && <p className="mt-3 text-sm">No tracked components are configured.</p>}
                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                        {components.map((component) => <ComponentLifecycleCard key={component.id} component={component} aircraftId={aircraft.id} canManage={can_certify} />)}
                    </div>
                </section>
                <MaintenanceAuthorityPanel aircraftId={aircraft.id} authorities={authorities} members={authority_members} canManage={can_manage} />
                {!can_certify && <p className="text-sm text-muted-foreground">Your current aircraft certification authority is required to sign off tasks or component changes.</p>}
                {can_manage && <section className="rounded-xl border p-5">
                    <h2 className="font-semibold">Schedule a maintenance obligation</h2>
                    <form onSubmit={schedule} className="mt-4 flex flex-col gap-3">
                        <label className="text-sm">Task title<Input required maxLength={180} disabled={form.processing} value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} /></label>
                        <label className="text-sm">Programme or manufacturer requirement reference<Input required maxLength={2000} disabled={form.processing} value={form.data.requirement_source} onChange={(event) => form.setData('requirement_source', event.target.value)} /></label>
                        <label className="flex flex-col gap-2 text-sm">Component
                            <select className="rounded-md border bg-background p-2" disabled={form.processing} value={form.data.uas_aircraft_component_id} onChange={(event) => form.setData('uas_aircraft_component_id', event.target.value)}>
                                <option value="">Aircraft task (date only)</option>
                                {components.filter((component) => component.status === 'active').map((component) => <option key={component.id} value={component.id}>{component.name}</option>)}
                            </select>
                        </label>
                        <div className="grid gap-3 sm:grid-cols-3">
                            <label className="text-sm">Due date<Input type="date" disabled={form.processing} value={form.data.due_at} onChange={(event) => form.setData('due_at', event.target.value)} /></label>
                            <label className="text-sm">Due at component hours<Input type="number" min="0" step="0.01" disabled={form.processing} value={form.data.due_hours} onChange={(event) => form.setData('due_hours', event.target.value)} /></label>
                            <label className="text-sm">Due at component cycles<Input type="number" min="0" step="1" disabled={form.processing} value={form.data.due_cycles} onChange={(event) => form.setData('due_cycles', event.target.value)} /></label>
                        </div>
                        <p className="text-sm text-muted-foreground">Enter at least one threshold. The first threshold reached blocks mission release until completion is recorded.</p>
                        <fieldset className="rounded-lg border p-3">
                            <legend className="px-1 text-sm font-medium">Repeat intervals (optional)</legend>
                            <p className="mb-3 text-sm text-muted-foreground">Leave all intervals empty for a single task. For recurring tasks, enter an interval for each configured threshold. The next task advances from the previous due thresholds; late completion does not extend the programme or skip missed intervals.</p>
                            <div className="grid gap-3 sm:grid-cols-3">
                                <label className="text-sm">Every days<Input type="number" min="1" step="1" disabled={form.processing} value={form.data.interval_days} onChange={(event) => form.setData('interval_days', event.target.value)} /></label>
                                <label className="text-sm">Every component hours<Input type="number" min="0.01" step="0.01" disabled={form.processing} value={form.data.interval_hours} onChange={(event) => form.setData('interval_hours', event.target.value)} /></label>
                                <label className="text-sm">Every flight cycles<Input type="number" min="1" step="1" disabled={form.processing} value={form.data.interval_cycles} onChange={(event) => form.setData('interval_cycles', event.target.value)} /></label>
                            </div>
                        </fieldset>
                        <div role="alert">{Object.entries(form.errors).map(([key, message]) => <InputError key={key} message={message} />)}</div>
                        <Button type="submit" disabled={form.processing}>{form.processing ? 'Scheduling…' : 'Schedule task'}</Button>
                    </form>
                </section>}
                <section className="flex flex-col gap-4">
                    <h2 className="font-semibold">Operator maintenance records</h2>
                    {tasks.data.length === 0 && <p className="text-sm">No maintenance tasks are recorded for this operator.</p>}
                    {tasks.data.map((task) => <TaskCard key={task.id} task={task} base={base} canManage={can_certify} components={components} canRelease={can_return_to_service && ['flight_restricted', 'unserviceable'].includes(aircraft.operational_status)} />)}
                    {maintenance_releases.map((release) => <p key={release.id} className="mt-3 text-sm">Return-to-service record #{release.id} · task #{release.maintenance_task_id} · {release.released_at} · certifying member #{release.released_by}</p>)}
                    <nav className="flex gap-4" aria-label="Maintenance history pages">{tasks.prev_page_url && <Link href={tasks.prev_page_url}>Previous</Link>}{tasks.next_page_url && <Link href={tasks.next_page_url}>Next</Link>}</nav>
                </section>
            </main>
        </AppLayout>
    );
}

function TaskCard({ task, base, canManage, components, canRelease }: { task: Task; base: string; canManage: boolean; components: Component[]; canRelease: boolean }) {
    const form = useForm({ work_performed: '', technician: '', parts_components: '', evidence_reference: '', certification: '', return_to_service_state: '', return_to_service_notes: '', completion_confirmed: false, end_recurrence: false, end_recurrence_reason: '' });
    const component = components.find((item) => item.id === task.uas_aircraft_component_id);
    function complete(event: FormEvent) {
        event.preventDefault();
        form.post(`${base}/${task.id}/complete`, { preserveScroll: true });
    }
    return <article className="rounded-xl border p-5">
        <h3 className="font-semibold">{task.title} · {task.due_state.status.replaceAll('_', ' ')}</h3>
        <p className="mt-2 text-sm">Requirement: {task.requirement_source}</p>
        {task.previous_task_id && <p className="mt-2 text-sm">Programme history: follows completed task #{task.previous_task_id}.</p>}
        {(task.interval_days !== null || task.interval_hours !== null || task.interval_cycles !== null) && <p className="mt-2 text-sm">Repeats every: {task.interval_days !== null ? `${task.interval_days} days; ` : ''}{task.interval_hours !== null ? `${task.interval_hours} hours; ` : ''}{task.interval_cycles !== null ? `${task.interval_cycles} flight cycles` : ''}</p>}
        {component && <p className="text-sm">Component: {component.name}</p>}
        <p className="mt-2 text-sm">Thresholds: {task.due_at ? `date ${task.due_at.slice(0, 10)}; ` : ''}{task.due_hours !== null ? `${task.due_hours} hours; ` : ''}{task.due_cycles !== null ? `${task.due_cycles} cycles` : ''}</p>
        {!task.completed_at && <p className="mt-2 text-sm">Remaining: {task.due_state.remaining_days !== null ? `${task.due_state.remaining_days} days; ` : ''}{task.due_state.remaining_hours !== null ? `${task.due_state.remaining_hours} hours; ` : ''}{task.due_state.remaining_cycles !== null ? `${task.due_state.remaining_cycles} cycles` : ''}</p>}
        {task.completed_at ? <details className="mt-3 text-sm"><summary>Completion evidence · {task.completed_at}</summary>{Object.entries(task.completion_evidence ?? {}).filter(([key]) => key !== 'completion_confirmed').map(([key, value]) => <p key={key} className="mt-2 break-words">{key.replaceAll('_', ' ')}: {typeof value === 'object' ? JSON.stringify(value) : String(value)}</p>)}</details> : canManage && <form onSubmit={complete} className="mt-4 flex flex-col gap-3">
            {(['work_performed', 'technician', 'parts_components', 'evidence_reference', 'certification'] as const).map((key) => <label key={key} className="text-sm">{key.replaceAll('_', ' ')}<Input required maxLength={key === 'technician' ? 180 : 2000} disabled={form.processing} value={form.data[key]} onChange={(event) => form.setData(key, event.target.value)} /></label>)}
            <label className="text-sm">Serviceability outcome<select required className="mt-1 block w-full rounded-md border bg-background p-2" disabled={form.processing} value={form.data.return_to_service_state} onChange={(event) => form.setData('return_to_service_state', event.target.value)}><option value="">Select outcome</option><option value="serviceable">Serviceable assessment</option><option value="flight_restricted">Flight restricted</option><option value="unserviceable">Unserviceable</option></select></label>
            <label className="text-sm">Serviceability evidence and restrictions<Input required maxLength={2000} disabled={form.processing} value={form.data.return_to_service_notes} onChange={(event) => form.setData('return_to_service_notes', event.target.value)} /></label>
            <p className="text-sm text-muted-foreground">Restricted or unserviceable outcomes block release. A serviceable assessment preserves existing aircraft restrictions; authorised return to service and all other release controls still apply.</p>
            <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.completion_confirmed} disabled={form.processing} onChange={(event) => form.setData('completion_confirmed', event.target.checked)} />I confirm this task is complete and the supporting evidence is recorded.</label>
            {component && ['removed', 'retired', 'awaiting_replacement'].includes(component.status) && (task.interval_days !== null || task.interval_hours !== null || task.interval_cycles !== null) && <>
                <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.end_recurrence} disabled={form.processing} onChange={(event) => form.setData('end_recurrence', event.target.checked)} />End this removed component's recurring programme with this completion.</label>
                {form.data.end_recurrence && <label className="text-sm">Programme closure reason<Input required maxLength={2000} disabled={form.processing} value={form.data.end_recurrence_reason} onChange={(event) => form.setData('end_recurrence_reason', event.target.value)} /></label>}
            </>}
            <p className="text-sm text-muted-foreground">Completion closes this obligation and creates the next task when repeat intervals are configured. Component life limits, defects and other release controls remain applicable.</p>
            <div role="alert">{Object.entries(form.errors).map(([key, message]) => <InputError key={key} message={message} />)}</div>
            <Button type="submit" disabled={form.processing || !form.data.completion_confirmed}>{form.processing ? 'Recording…' : 'Record completion'}</Button>
        </form>}
        {canRelease && task.completed_at && task.completion_evidence?.return_to_service_state === 'serviceable' && <ReturnToServiceForm taskId={task.id} base={base} />}
    </article>;
}

function ReturnToServiceForm({ taskId, base }: { taskId: number; base: string }) {
    const form = useForm({ evidence_reference: '', release_notes: '', release_confirmed: false });
    function release(event: FormEvent) {
        event.preventDefault();
        form.post(`${base}/${taskId}/return-to-service`, { preserveScroll: true });
    }
    return <form onSubmit={release} className="mt-4 flex flex-col gap-3 rounded-lg border p-4">
        <h4 className="font-medium">Maintenance return to service</h4>
        <p className="text-sm text-muted-foreground">Use a new repair or inspection task completed after this operator's maintenance restriction. Due maintenance, component limits, installation gaps, blocking defects and active flights must be resolved. Flight release still requires its separate compliance checks.</p>
        <label className="text-sm">Release evidence reference<Input required maxLength={2000} disabled={form.processing} value={form.data.evidence_reference} onChange={(event) => form.setData('evidence_reference', event.target.value)} /></label>
        <label className="text-sm">Release notes<Input required maxLength={2000} disabled={form.processing} value={form.data.release_notes} onChange={(event) => form.setData('release_notes', event.target.value)} /></label>
        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.release_confirmed} disabled={form.processing} onChange={(event) => form.setData('release_confirmed', event.target.checked)} />I confirm the repair evidence and my authority to return this aircraft to service.</label>
        <div role="alert">{Object.entries(form.errors).map(([key, message]) => <InputError key={key} message={message} />)}</div>
        <Button disabled={form.processing || !form.data.release_confirmed}>Record return to service</Button>
    </form>;
}
