import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { ComplianceNotification } from './types';

export default function Show({ notification }: { notification: ComplianceNotification }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifications', href: '/compliance-notifications' }, { title: notification.subject, href: `/compliance-notifications/${notification.id}` }];
    const { data, setData, put, processing, errors } = useForm({ status: notification.status, failure_reason: notification.failure_reason ?? '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        put(`/compliance-notifications/${notification.id}/status`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={notification.subject} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title={notification.subject} description={`${notification.notification_type.replaceAll('_', ' ')} / ${notification.channel.replaceAll('_', ' ')}`} />
                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Lifecycle"><div className="mb-3 flex gap-2"><StatusBadge value={notification.status} /><StatusBadge value={notification.priority} /></div><Detail label="Due at" value={notification.due_at} /><Detail label="Sent at" value={notification.sent_at} /><Detail label="Read at" value={notification.read_at} /><Detail label="Acknowledged at" value={notification.acknowledged_at} /><Detail label="Delivery attempts" value={notification.delivery_attempts.toString()} /></Panel>
                    <Panel title="Message"><Detail label="Recipient" value={notification.recipient} /><Detail label="Requirement" value={notification.requirement_id} /><Detail label="Idempotency key" value={notification.idempotency_key} /><Detail label="Body" value={notification.message} /><Detail label="Failure" value={notification.failure_reason} /></Panel>
                    <Panel title="Status Update"><form onSubmit={submit} className="space-y-4"><div><Label>Status</Label><select value={data.status} onChange={(event) => setData('status', event.target.value)} className="mt-2 h-9 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="pending">Pending</option><option value="sent">Sent</option><option value="read">Read</option><option value="acknowledged">Acknowledged</option><option value="failed">Failed</option><option value="cancelled">Cancelled</option></select><InputError message={errors.status} className="mt-2" /></div><div><Label>Failure reason</Label><textarea value={data.failure_reason} onChange={(event) => setData('failure_reason', event.target.value)} className="mt-2 min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /><InputError message={errors.failure_reason} className="mt-2" /></div><Button disabled={processing}>Update status</Button></form></Panel>
                </div>
            </div>
        </AppLayout>
    );
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) { return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><h2 className="mb-4 text-base font-semibold">{title}</h2><div className="space-y-3">{children}</div></section>; }
function Detail({ label, value }: { label: string; value?: string | null }) { return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{value || 'Not captured'}</dd></div>; }
