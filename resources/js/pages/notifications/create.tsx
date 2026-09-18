import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { ComplianceNotificationOptions } from './types';

type NotificationFormData = {
    user_id: string;
    requirement_id: string;
    notification_type: string;
    idempotency_key: string;
    channel: string;
    priority: string;
    subject: string;
    message: string;
    due_at: string;
};

export default function Create({ options }: { options: ComplianceNotificationOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifications', href: '/compliance-notifications' }, { title: 'New Notification', href: '/compliance-notifications/create' }];
    const { data, setData, post, processing, errors, transform } = useForm<NotificationFormData>({
        user_id: '',
        requirement_id: 'FR-NOT-002',
        notification_type: 'application_deadline',
        idempotency_key: '',
        channel: 'in_application',
        priority: 'normal',
        subject: '',
        message: '',
        due_at: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            user_id: payload.user_id || null,
            requirement_id: payload.requirement_id || null,
            idempotency_key: payload.idempotency_key || null,
            due_at: payload.due_at || null,
        }));
        post('/compliance-notifications');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New compliance notification" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="New compliance notification" description="Plan an auditable notification for a compliance event, deadline or regulatory change." />
                <form onSubmit={submit} className="space-y-8">
                    <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <Field label="Recipient" error={errors.user_id}><Select value={data.user_id} onChange={(value) => setData('user_id', value)}><option value="">Unassigned</option>{options.users.map((user) => <option key={user.id} value={user.id}>{user.label}</option>)}</Select></Field>
                        <Field label="Requirement ID" error={errors.requirement_id}><Input value={data.requirement_id} onChange={(event) => setData('requirement_id', event.target.value)} /></Field>
                        <Field label="Type" error={errors.notification_type}><Select value={data.notification_type} onChange={(value) => setData('notification_type', value)}>{Object.entries(options.types).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</Select></Field>
                        <Field label="Channel" error={errors.channel}><Select value={data.channel} onChange={(value) => setData('channel', value)}>{Object.entries(options.channels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</Select></Field>
                        <Field label="Priority" error={errors.priority}><Select value={data.priority} onChange={(value) => setData('priority', value)}>{Object.entries(options.priorities).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</Select></Field>
                        <Field label="Due at" error={errors.due_at}><Input type="datetime-local" value={data.due_at} onChange={(event) => setData('due_at', event.target.value)} /></Field>
                        <Field label="Idempotency key" error={errors.idempotency_key}><Input value={data.idempotency_key} onChange={(event) => setData('idempotency_key', event.target.value)} /></Field>
                        <Field label="Subject" error={errors.subject}><Input value={data.subject} onChange={(event) => setData('subject', event.target.value)} /></Field>
                    </section>
                    <Field label="Message" error={errors.message}><textarea value={data.message} onChange={(event) => setData('message', event.target.value)} className="min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></Field>
                    <Button disabled={processing}><Save />Plan notification</Button>
                </form>
            </div>
        </AppLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) { return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>; }
function Select({ value, onChange, children }: { value: string; onChange: (value: string) => void; children: ReactNode }) { return <select value={value} onChange={(event) => onChange(event.target.value)} className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">{children}</select>; }
