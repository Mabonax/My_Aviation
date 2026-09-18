import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AircraftModel } from './types';

export default function Show({ model }: { model: AircraftModel }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Aircraft Catalogue', href: '/aircraft-catalogue' }, { title: `${model.manufacturer.name} ${model.model}`, href: `/aircraft-catalogue/${model.id}` }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${model.manufacturer.name} ${model.model}`} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title={`${model.manufacturer.name} ${model.model}`} description={model.primary_use || model.aircraft_type} actions={<Button asChild><Link href={`/aircraft/create?aircraft_model_id=${model.id}`}>Add Aircraft</Link></Button>} />
                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Technical Summary"><StatusBadge value={model.catalogue_status} /><Detail label="Family" value={model.family} /><Detail label="Aircraft type" value={model.aircraft_type} /><Detail label="Flight time" value={model.max_flight_time_min ? `${model.max_flight_time_min} min` : null} /><Detail label="MTOW" value={model.mtow_kg ? `${model.mtow_kg} kg` : null} /><Detail label="Max payload" value={model.max_payload_kg ? `${model.max_payload_kg} kg` : null} /><Detail label="Max speed" value={model.max_speed_m_s ? `${model.max_speed_m_s} m/s` : null} /><Detail label="IP rating" value={model.ip_rating} /></Panel>
                    <Panel title="Payload and Navigation"><Detail label="GNSS" value={model.gnss} /><Detail label="Camera / payload" value={model.camera_payload_summary} /><Detail label="Remote ID" value={model.remote_id} /><Detail label="Dimensions" value={model.dimensions} /><Detail label="Operating temperature" value={model.operating_temp_c} /></Panel>
                    <Panel title="Provenance"><Detail label="Source priority" value={model.source_priority} /><Detail label="Verified" value={model.verified_at} /><Detail label="Source URL" value={model.source_url} /><Detail label="Notes" value={model.notes} /></Panel>
                    <Panel title="Media Governance"><Detail label="Image source reference" value={model.image_source_url} /><Detail label="Image license status" value={model.image_license_status} /><Detail label="Media status" value={model.media_status} /></Panel>
                </div>
            </div>
        </AppLayout>
    );
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) {
    return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><h2 className="mb-4 text-base font-semibold">{title}</h2><div className="space-y-3">{children}</div></section>;
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 break-words text-sm">{value || 'Not captured'}</dd></div>;
}
