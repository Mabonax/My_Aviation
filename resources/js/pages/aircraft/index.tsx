import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Plane, ArrowRight, Plus } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Aircraft', href: '/aircraft' }];

interface AircraftRow {
    id: number;
    registration: string;
    manufacturer: string;
    model: string;
    serial_number: string;
    operational_status: string;
    operator_names: string[];
    readiness: {
        status: 'green' | 'amber' | 'red';
        label: string;
    };
}

export default function Index({ aircraft }: { aircraft: AircraftRow[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Aircraft" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Aircraft" description="Operator-scoped aircraft register for mission planning and fleet governance." actions={<div className="flex gap-2"><Button variant="outline" asChild><Link href="/aircraft-catalogue">Catalogue</Link></Button><Button asChild><Link href="/aircraft/create">Add Aircraft<Plus /></Link></Button></div>} />
                <section className="rounded-lg border bg-card text-card-foreground shadow-xs">
                    {aircraft.length === 0 ? (
                        <EmptyState icon={Plane} title="No aircraft available" description="Aircraft appear here once assigned to an operator you can access." />
                    ) : (
                        <div className="divide-y">
                            {aircraft.map((item) => (
                                <div key={item.id} className="grid gap-3 px-4 py-4 md:grid-cols-12 md:items-center">
                                    <div className="md:col-span-3">
                                        <div className="font-medium">{item.registration}</div>
                                        <div className="text-sm text-muted-foreground">{item.serial_number}</div>
                                    </div>
                                    <div className="text-sm text-muted-foreground md:col-span-3">{item.manufacturer} {item.model}</div>
                                    <div className="text-sm text-muted-foreground md:col-span-3">{item.operator_names.length ? item.operator_names.join(', ') : 'No operator assignment'}</div>
                                    <div className="md:col-span-1"><StatusBadge value={item.readiness.label} /></div>
                                    <div className="md:col-span-1"><StatusBadge value={item.operational_status.replaceAll('_', ' ')} /></div>
                                    <div className="flex justify-end md:col-span-1"><Button variant="ghost" size="icon" asChild aria-label={`View ${item.registration}`}><Link href={`/aircraft/${item.id}`}><ArrowRight className="size-4" /></Link></Button></div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
