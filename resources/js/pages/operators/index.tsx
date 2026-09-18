import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Building2, Eye, Plus } from 'lucide-react';

type OperatorRow = {
    id: number;
    legal_entity: string;
    trading_name: string | null;
    uasoc_number: string | null;
    status: string;
    certificate_expiry_date: string | null;
    accountable_manager: string;
    operating_bases_count: number;
    approved_aircraft_count: number;
    approved_pilots_count: number;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Operators', href: '/operators' }];

export default function Index({ operators }: { operators: OperatorRow[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="UAS operators" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="UAS operators" description="FR-OPS-001 operator certificate holder profiles, post holders, bases, fleet, pilots and OpsSpecs." actions={<Button asChild><Link href="/operators/create"><Plus />New operator</Link></Button>} />
                <div className="overflow-hidden rounded-lg border bg-card text-card-foreground shadow-xs">
                    <div className="grid grid-cols-12 border-b bg-muted/50 px-4 py-3 text-xs font-medium uppercase text-muted-foreground">
                        <span className="col-span-5 md:col-span-3">Operator</span>
                        <span className="col-span-2 hidden md:block">UASOC</span>
                        <span className="col-span-2">Status</span>
                        <span className="col-span-2 hidden lg:block">Approvals</span>
                        <span className="col-span-5 text-right md:col-span-3 lg:col-span-1">View</span>
                    </div>
                    {operators.length === 0 ? (
                        <EmptyState icon={Building2} title="No operator profiles" description="Create the operator governance master record before adding certificate lifecycle or manual controls." action={<Link href="/operators/create">Create operator</Link>} />
                    ) : operators.map((operator) => (
                        <div key={operator.id} className="grid grid-cols-12 items-center border-b px-4 py-3 text-sm last:border-b-0">
                            <div className="col-span-5 md:col-span-3"><div className="font-medium">{operator.legal_entity}</div><div className="text-muted-foreground">{operator.trading_name || operator.accountable_manager}</div></div>
                            <div className="col-span-2 hidden text-muted-foreground md:block">{operator.uasoc_number || 'Pending'}</div>
                            <div className="col-span-2"><StatusBadge value={operator.status.replaceAll('_', ' ')} /></div>
                            <div className="col-span-2 hidden text-muted-foreground lg:block">{operator.approved_aircraft_count} aircraft / {operator.approved_pilots_count} pilots</div>
                            <div className="col-span-5 text-right md:col-span-3 lg:col-span-1"><Button variant="ghost" size="icon" asChild aria-label={`View ${operator.legal_entity}`}><Link href={`/operators/${operator.id}`}><Eye /></Link></Button></div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}