import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

export type TrackedComponent = {
    id: number; name: string; status: string; installed_at: string | null;
    accumulated_hours: string; accumulated_cycles: number; life_limit_hours: string | null;
    life_limit_cycles: number | null; serial_number: string | null; removed_at: string | null;
    replaces_component_id: number | null; removal_evidence?: Record<string, unknown>;
    installation_evidence?: Record<string, unknown>;
};

export default function ComponentLifecycleCard({ component, aircraftId, canManage }: { component: TrackedComponent; aircraftId: number; canManage: boolean }) {
    const [mode, setMode] = useState<'remove' | 'replace' | null>(null);
    const form = useForm({ reason: '', technician: '', evidence_reference: '', certification: '', change_confirmed: false,
        serial_number: '', accumulated_hours: '', accumulated_cycles: '', life_limit_hours: '', life_limit_cycles: '' });
    function submit(event: FormEvent) {
        event.preventDefault();
        if (!mode) return;
        form.post(`/aircraft/${aircraftId}/components/${component.id}/${mode}`, {
            preserveScroll: true, onSuccess: () => { form.reset(); setMode(null); },
        });
    }
    return <article className="rounded-lg border p-3 text-sm">
        <h3 className="font-medium">{component.name} · {component.status.replaceAll('_', ' ')}</h3>
        <p>Serial: {component.serial_number ?? 'Not captured'}</p>
        <p>Hours: {component.accumulated_hours} / {component.life_limit_hours ?? 'No configured limit'}</p>
        <p>Flight cycles: {component.accumulated_cycles} / {component.life_limit_cycles ?? 'No configured limit'}</p>
        <p>Installed: {component.installed_at ?? 'Not captured'}</p>
        {component.removed_at && <p>Removed: {component.removed_at}</p>}
        {component.replaces_component_id && <p>Replaces component #{component.replaces_component_id}</p>}
        {(['removal_evidence', 'installation_evidence'] as const).map((key) => component[key] && <details key={key} className="mt-2">
            <summary>{key.replaceAll('_', ' ')}</summary>
            {Object.entries(component[key] ?? {}).map(([field, value]) => <p key={field} className="mt-1 break-words">{field.replaceAll('_', ' ')}: {String(value)}</p>)}
        </details>)}
        {canManage && ['active', 'awaiting_replacement'].includes(component.status) && <div className="mt-3 flex gap-2">
            <Button type="button" variant="outline" disabled={form.processing} onClick={() => { setMode('replace'); form.clearErrors(); }}>Replace component</Button>
            {component.status === 'active' && <Button type="button" variant="outline" disabled={form.processing} onClick={() => { setMode('remove'); form.clearErrors(); }}>Remove component</Button>}
        </div>}
        {mode && <form onSubmit={submit} className="mt-4 flex flex-col gap-3">
            <p>{mode === 'remove' ? 'Removal blocks readiness until a replacement is installed.' : 'The new component receives its own serial, initial usage and life limits. The previous component history is retained.'} Open maintenance obligations still require explicit closure evidence.</p>
            {(['reason', 'technician', 'evidence_reference', 'certification'] as const).map((key) => <label key={key}>{key.replaceAll('_', ' ')}<Input required disabled={form.processing} maxLength={key === 'technician' ? 180 : 2000} value={form.data[key]} onChange={(event) => form.setData(key, event.target.value)} /></label>)}
            {mode === 'replace' && <>
                <label>Replacement serial<Input required disabled={form.processing} maxLength={255} value={form.data.serial_number} onChange={(event) => form.setData('serial_number', event.target.value)} /></label>
                <div className="grid gap-3 sm:grid-cols-2">
                    {(['accumulated_hours', 'accumulated_cycles', 'life_limit_hours', 'life_limit_cycles'] as const).map((key) => <label key={key}>{key.replaceAll('_', ' ')}<Input type="number" required={key.startsWith('accumulated')} min={key.startsWith('accumulated') ? '0' : key.endsWith('hours') ? '0.01' : '1'} step={key.endsWith('hours') ? '0.01' : '1'} disabled={form.processing} value={form.data[key]} onChange={(event) => form.setData(key, event.target.value)} /></label>)}
                </div>
                <p>Declare initial usage explicitly, including zero for an unused part. Blank limits retain the previous configured limits. Enter changed limits only with supporting manufacturer or operator evidence.</p>
            </>}
            <label className="flex items-center gap-2"><input type="checkbox" checked={form.data.change_confirmed} disabled={form.processing} onChange={(event) => form.setData('change_confirmed', event.target.checked)} />I confirm the physical change and supporting evidence.</label>
            <div role="alert">{Object.entries(form.errors).map(([key, message]) => <InputError key={key} message={message} />)}</div>
            <div className="flex gap-2"><Button type="submit" disabled={form.processing || !form.data.change_confirmed}>{form.processing ? 'Recording…' : 'Record component change'}</Button><Button type="button" variant="outline" disabled={form.processing} onClick={() => setMode(null)}>Cancel</Button></div>
        </form>}
    </article>;
}
