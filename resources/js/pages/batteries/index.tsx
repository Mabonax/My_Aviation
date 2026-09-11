import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BatteryCharging, Plus } from 'lucide-react';

type BatteryInventoryItem = {
    id: number;
    battery_uid: string;
    manufacturer: string;
    model: string | null;
    serial_number: string;
    compatible_aircraft: { id: number; registration: string } | null;
    cycle_count: number;
    maximum_cycles: number | null;
    health_status: string;
    last_used_at: string | null;
    retirement_status: string;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Batteries', href: '/batteries' }];

export default function Index({ batteries }: { batteries: BatteryInventoryItem[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Battery inventory" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Battery inventory"
                    description="FR-BAT-001 electric UAS battery lifecycle, cycle and health records."
                    actions={<Button asChild><Link href="/batteries/create"><Plus />New battery</Link></Button>}
                />

                <div className="overflow-hidden rounded-lg border bg-card text-card-foreground shadow-xs">
                    <div className="grid grid-cols-12 border-b bg-muted/50 px-4 py-3 text-xs font-medium uppercase text-muted-foreground">
                        <span className="col-span-5 md:col-span-3">Battery</span>
                        <span className="col-span-3 hidden md:block">Aircraft</span>
                        <span className="col-span-2">Cycles</span>
                        <span className="col-span-3 md:col-span-2">Health</span>
                        <span className="col-span-2">Status</span>
                    </div>
                    {batteries.length === 0 ? (
                        <EmptyState icon={BatteryCharging} title="No batteries captured" description="Create the first battery inventory record before assigning batteries to electric UAS missions." action={<Link href="/batteries/create">Create battery</Link>} />
                    ) : (
                        batteries.map((battery) => (
                            <div key={battery.id} className="grid grid-cols-12 items-center border-b px-4 py-3 text-sm last:border-b-0">
                                <div className="col-span-5 md:col-span-3">
                                    <div className="font-medium">{battery.battery_uid}</div>
                                    <div className="text-muted-foreground">{battery.manufacturer} {battery.model ?? ''} / {battery.serial_number}</div>
                                </div>
                                <div className="col-span-3 hidden text-muted-foreground md:block">{battery.compatible_aircraft?.registration ?? 'Fleet compatible'}</div>
                                <div className="col-span-2 text-muted-foreground">{battery.cycle_count}{battery.maximum_cycles ? ` / ${battery.maximum_cycles}` : ''}</div>
                                <div className="col-span-3 md:col-span-2"><StatusBadge value={battery.health_status.replaceAll('_', ' ')} /></div>
                                <div className="col-span-2"><StatusBadge value={battery.retirement_status} /></div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </AppLayout>
    );
}