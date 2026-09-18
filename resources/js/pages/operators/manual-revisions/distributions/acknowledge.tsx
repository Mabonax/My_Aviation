import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent } from 'react';
import { ManualDistributionAcknowledgement, OperationsManualRevision } from '../../types';

export default function Acknowledge({ distribution, manualRevision }: { distribution: ManualDistributionAcknowledgement; manualRevision: OperationsManualRevision }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Operators', href: '/operators' },
        { title: manualRevision.operator.legal_entity, href: `/operators/${manualRevision.operator.id}` },
        { title: manualRevision.revision_code, href: `/operations-manual-revisions/${manualRevision.id}` },
        { title: 'Acknowledge', href: `/operations-manual-distributions/${distribution.id}/acknowledge` },
    ];
    const { data, setData, put, processing, errors } = useForm<{ readership_confirmed: boolean; acknowledgement_notes: string }>({ readership_confirmed: false, acknowledgement_notes: distribution.acknowledgement_notes ?? '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        put(`/operations-manual-distributions/${distribution.id}/acknowledge`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Acknowledge manual revision" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Acknowledge manual revision" description={`${manualRevision.manual_name} ${manualRevision.revision_code}`} />
                <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 className="text-base font-semibold">{distribution.recipient_name}</h2>
                            <p className="text-sm text-muted-foreground">{distribution.recipient_role}{distribution.recipient_email ? ` / ${distribution.recipient_email}` : ''}</p>
                        </div>
                        <StatusBadge value={distribution.acknowledgement_status.replaceAll('_', ' ')} />
                    </div>
                    <dl className="grid gap-3 text-sm md:grid-cols-2">
                        <Detail label="Manual" value={manualRevision.manual_name} />
                        <Detail label="Revision" value={manualRevision.revision_code} />
                        <Detail label="Effective date" value={manualRevision.effective_date} />
                        <Detail label="Acknowledged at" value={distribution.acknowledged_at} />
                    </dl>
                </section>

                <form onSubmit={submit} className="space-y-6">
                    <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
                        <Label className="flex items-start gap-3 text-sm font-normal">
                            <input type="checkbox" checked={data.readership_confirmed} onChange={(event) => setData('readership_confirmed', event.target.checked)} className="mt-1" />
                            <span>I acknowledge receipt and readership of this Operations Manual revision.</span>
                        </Label>
                        <InputError message={errors.readership_confirmed} className="mt-2" />
                        <div className="mt-5">
                            <Label>Notes</Label>
                            <textarea value={data.acknowledgement_notes} onChange={(event) => setData('acknowledgement_notes', event.target.value)} className="mt-2 min-h-28 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" />
                            <InputError message={errors.acknowledgement_notes} className="mt-2" />
                        </div>
                    </section>
                    <div className="flex flex-wrap items-center gap-3">
                        <Button disabled={processing || distribution.acknowledgement_status === 'acknowledged'}><Save />Record acknowledgement</Button>
                        <Button variant="outline" asChild><Link href={`/operations-manual-revisions/${manualRevision.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1">{value || 'Not captured'}</dd></div>;
}
