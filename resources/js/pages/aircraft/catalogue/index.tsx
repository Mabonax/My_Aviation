import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plane, ArrowRight, Plus, Search } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { AircraftCataloguePage, CatalogueFilters } from './types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Aircraft Catalogue', href: '/aircraft-catalogue' }];

export default function Index({ models, filters, activeFilters }: { models: AircraftCataloguePage; filters: CatalogueFilters; activeFilters: Record<string, string | undefined> }) {
    const [search, setSearch] = useState(activeFilters.search || '');
    const [manufacturer, setManufacturer] = useState(activeFilters.manufacturer || 'all');
    const [aircraftType, setAircraftType] = useState(activeFilters.aircraft_type || 'all');
    const [status, setStatus] = useState(activeFilters.status || 'current');

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get('/aircraft-catalogue', {
            search: search || undefined,
            manufacturer: manufacturer === 'all' ? undefined : manufacturer,
            aircraft_type: aircraftType === 'all' ? undefined : aircraftType,
            status: status === 'all' ? undefined : status,
        }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Aircraft Catalogue" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Aircraft Catalogue" description="Governed manufacturer model data for selecting aircraft without duplicating technical specifications into physical asset records." actions={<Button asChild><Link href="/aircraft/create">Add Aircraft<Plus /></Link></Button>} />
                <form onSubmit={submit} className="grid gap-3 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-5">
                    <div className="space-y-2 md:col-span-2"><Label>Search</Label><Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Manufacturer, model or use" /></div>
                    <SelectFilter label="Manufacturer" value={manufacturer} onChange={setManufacturer} options={filters.manufacturers.map((item) => ({ value: item.label, label: item.label }))} />
                    <SelectFilter label="Type" value={aircraftType} onChange={setAircraftType} options={filters.aircraft_types.map((type) => ({ value: type, label: type }))} />
                    <div className="flex items-end gap-2"><SelectFilter label="Status" value={status} onChange={setStatus} options={Object.entries(filters.statuses).map(([value, label]) => ({ value, label }))} /><Button aria-label="Apply filters"><Search className="size-4" /></Button></div>
                </form>
                <section className="rounded-lg border bg-card text-card-foreground shadow-xs">
                    {models.data.length === 0 ? <EmptyState icon={Plane} title="No catalogue models found" description="Adjust the search or import the aircraft catalogue source file." /> : (
                        <div className="divide-y">
                            {models.data.map((model) => (
                                <div key={model.id} className="grid gap-3 px-4 py-4 lg:grid-cols-12 lg:items-center">
                                    <div className="lg:col-span-3"><div className="font-medium">{model.manufacturer.name} {model.model}</div><div className="text-sm text-muted-foreground">{model.family || model.aircraft_type}</div></div>
                                    <div className="text-sm text-muted-foreground lg:col-span-3">{model.primary_use || 'Use not captured'}</div>
                                    <div className="grid grid-cols-2 gap-2 text-sm lg:col-span-4">
                                        <Metric label="Flight" value={model.max_flight_time_min ? `${model.max_flight_time_min} min` : null} />
                                        <Metric label="MTOW" value={model.mtow_kg ? `${model.mtow_kg} kg` : null} />
                                        <Metric label="Payload" value={model.max_payload_kg ? `${model.max_payload_kg} kg` : null} />
                                        <Metric label="IP" value={model.ip_rating} />
                                    </div>
                                    <div className="lg:col-span-1"><StatusBadge value={model.catalogue_status} /></div>
                                    <div className="flex justify-end lg:col-span-1"><Button variant="ghost" size="icon" asChild aria-label={`View ${model.model}`}><Link href={`/aircraft-catalogue/${model.id}`}><ArrowRight className="size-4" /></Link></Button></div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}

function SelectFilter({ label, value, onChange, options }: { label: string; value: string; onChange: (value: string) => void; options: Array<{ value: string; label: string }> }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">All</SelectItem>{options.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent></Select></div>;
}

function Metric({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd>{value || 'N/A'}</dd></div>;
}
