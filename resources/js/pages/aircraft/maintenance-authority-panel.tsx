import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

export type Authority = { id: number; user_id: number; valid_until: string; evidence_reference: string; revoked_at: string | null; revocation_reason: string | null; can_return_to_service: boolean };
export type AuthorityMember = { id: number; name: string };

export default function MaintenanceAuthorityPanel({ aircraftId, authorities, members, canManage }: { aircraftId: number; authorities: Authority[]; members: AuthorityMember[]; canManage: boolean }) {
    const form = useForm({ user_id: '', valid_until: '', evidence_reference: '', authority_confirmed: false, can_return_to_service: false });
    const base = `/aircraft/${aircraftId}/maintenance-authorities`;
    function grant(event: FormEvent) {
        event.preventDefault();
        form.post(base, { preserveScroll: true, onSuccess: () => form.reset() });
    }
    return <section className="rounded-xl border p-5">
        <h2 className="font-semibold">Maintenance certification authority</h2>
        <p className="mt-2 text-sm text-muted-foreground">Certification requires current authority for this aircraft and operator, plus active operator membership. A manager verifies another member's supporting evidence. Expired or revoked grants cannot be used for sign-off. Records represent operator-declared authority.</p>
        {canManage && <form onSubmit={grant} className="mt-4 flex flex-col gap-3">
            <label className="text-sm">Operator member<select required className="mt-1 block w-full rounded-md border bg-background p-2" disabled={form.processing} value={form.data.user_id} onChange={(event) => form.setData('user_id', event.target.value)}><option value="">Select member</option>{members.map((member) => <option key={member.id} value={member.id}>{member.name} · #{member.id}</option>)}</select></label>
            <label className="text-sm">Valid through<Input required type="date" disabled={form.processing} value={form.data.valid_until} onChange={(event) => form.setData('valid_until', event.target.value)} /></label>
            <label className="text-sm">Authority evidence reference<Input required maxLength={2000} disabled={form.processing} value={form.data.evidence_reference} onChange={(event) => form.setData('evidence_reference', event.target.value)} /></label>
            <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.can_return_to_service} disabled={form.processing} onChange={(event) => form.setData('can_return_to_service', event.target.checked)} />The verified authority also covers maintenance return to service.</label>
            <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.authority_confirmed} disabled={form.processing} onChange={(event) => form.setData('authority_confirmed', event.target.checked)} />I verified the member's authority and its scope for this aircraft.</label>
            <div role="alert">{Object.entries(form.errors).map(([key, message]) => <InputError key={key} message={message} />)}</div>
            <Button disabled={form.processing || !form.data.authority_confirmed}>Record authority</Button>
        </form>}
        {authorities.length === 0 && <p className="mt-3 text-sm">No certification authority is recorded for this operator and aircraft.</p>}
        {authorities.map((authority) => <AuthorityRow key={authority.id} authority={authority} name={members.find((member) => member.id === authority.user_id)?.name ?? `Member #${authority.user_id}`} base={base} canManage={canManage} />)}
    </section>;
}

function AuthorityRow({ authority, name, base, canManage }: { authority: Authority; name: string; base: string; canManage: boolean }) {
    const form = useForm({ reason: '' });
    function revoke(event: FormEvent) {
        event.preventDefault();
        form.post(`${base}/${authority.id}/revoke`, { preserveScroll: true });
    }
    return <article className="mt-4 rounded-lg border p-4 text-sm">
        <p className="font-medium">{name} · grant #{authority.id}</p>
        <p className="mt-1">Valid through {authority.valid_until.slice(0, 10)}{authority.revoked_at ? ` · Revoked ${authority.revoked_at}` : ''}</p>
        <p className="mt-1 break-words">Evidence: {authority.evidence_reference}</p>
        <p className="mt-1">Scope: certification{authority.can_return_to_service ? ' and maintenance return to service' : ''}.</p>
        {authority.revocation_reason && <p className="mt-1">Revocation reason: {authority.revocation_reason}</p>}
        {canManage && !authority.revoked_at && <form onSubmit={revoke} className="mt-3 flex flex-col gap-2">
            <label>Revocation reason<Input required maxLength={2000} disabled={form.processing} value={form.data.reason} onChange={(event) => form.setData('reason', event.target.value)} /></label>
            <InputError message={form.errors.reason} />
            <Button variant="outline" disabled={form.processing}>Revoke authority</Button>
        </form>}
    </article>;
}
