import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

type Component = { id: number; name: string; status: string; installed_at: string | null; accumulated_hours: string; accumulated_cycles: number; life_limit_hours: string | null; life_limit_cycles: number | null };
type Task = { id: number; title: string; requirement_source: string; due_at: string | null; due_hours: string | null; due_cycles: number | null; interval_days: number | null; interval_hours: string | null; interval_cycles: number | null; previous_task_id: number | null; uas_aircraft_component_id: number | null; completed_at: string | null; completion_evidence: Record<string, string> | null; due_state: { status: string; remaining_days: number | null; remaining_hours: number | null; remaining_cycles: number | null } };
type Props = { aircraft: { id: number; registration: string }; components: Component[]; tasks: { data: Task[]; prev_page_url: string | null; next_page_url: string | null }; summary: { status: string; blocking_reasons: string[]; review_reasons: string[] }; can_manage: boolean };

export default function Maintenance({ aircraft, components, tasks, summary, can_manage }: Props) {
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
                    {[...summary.blocking_reasons, ...summary.review_reasons].map((reason, index) => <p key={index} className="mt-2 text-sm">{reason}</p>)}
                    <p className="mt-2 text-sm text-muted-foreground">Readiness checks all configured aircraft obligations. This workspace shows tasks belonging to the selected operator. Dates become due at the start of the stated day. Hours and cycles are absolute component totals; a cycle is one accepted mission flight.</p>
                </section>
                <section className="rounded-xl border p-5">
                    <h2 className="font-semibold">Component usage and life limits</h2>
                    <p className="mt-2 text-sm text-muted-foreground">Recorded flights update active components installed before takeoff. Existing usage is retained; historical flights are not backfilled automatically.</p>
                    {components.length === 0 && <p className="mt-3 text-sm">No tracked components are configured.</p>}
                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                        {components.map((component) => <div key={component.id} className="rounded-lg border p-3 text-sm">
                            <h3 className="font-medium">{component.name} · {component.status}</h3>
                            <p>Hours: {component.accumulated_hours} / {component.life_limit_hours ?? 'No configured limit'}</p>
                            <p>Flight cycles: {component.accumulated_cycles} / {component.life_limit_cycles ?? 'No configured limit'}</p>
                            {!component.installed_at && <p>Installation date required for automatic usage tracking.</p>}
                        </div>)}
                    </div>
                </section>
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
                    {tasks.data.map((task) => <TaskCard key={task.id} task={task} base={base} canManage={can_manage} components={components} />)}
                    <nav className="flex gap-4" aria-label="Maintenance history pages">{tasks.prev_page_url && <Link href={tasks.prev_page_url}>Previous</Link>}{tasks.next_page_url && <Link href={tasks.next_page_url}>Next</Link>}</nav>
                </section>
            </main>
        </AppLayout>
    );
}

function TaskCard({ task, base, canManage, components }: { task: Task; base: string; canManage: boolean; components: Component[] }) {
    const form = useForm({ work_performed: '', technician: '', parts_components: '', evidence_reference: '', certification: '', completion_confirmed: false });
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
        {task.completed_at ? <details className="mt-3 text-sm"><summary>Completion evidence · {task.completed_at}</summary>{Object.entries(task.completion_evidence ?? {}).filter(([key]) => key !== 'completion_confirmed').map(([key, value]) => <p key={key} className="mt-2 break-words">{key.replaceAll('_', ' ')}: {String(value)}</p>)}</details> : canManage && <form onSubmit={complete} className="mt-4 flex flex-col gap-3">
            {(['work_performed', 'technician', 'parts_components', 'evidence_reference', 'certification'] as const).map((key) => <label key={key} className="text-sm">{key.replaceAll('_', ' ')}<Input required maxLength={key === 'technician' ? 180 : 2000} disabled={form.processing} value={form.data[key]} onChange={(event) => form.setData(key, event.target.value)} /></label>)}
            <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.completion_confirmed} disabled={form.processing} onChange={(event) => form.setData('completion_confirmed', event.target.checked)} />I confirm this task is complete and the supporting evidence is recorded.</label>
            <p className="text-sm text-muted-foreground">Completion closes this obligation and creates the next task when repeat intervals are configured. Component life limits, defects and other release controls remain applicable.</p>
            <div role="alert">{Object.entries(form.errors).map(([key, message]) => <InputError key={key} message={message} />)}</div>
            <Button type="submit" disabled={form.processing || !form.data.completion_confirmed}>{form.processing ? 'Recording…' : 'Record completion'}</Button>
        </form>}
    </article>;
}
