import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Plus } from 'lucide-react';

type DefectItem = {
    id: number;
    defect_number: string;
    source: string;
    severity: string;
    status: string;
    serviceability_impact: string;
    title: string;
    reported_at: string | null;
    aircraft: { id: number; registration: string; operational_status: string } | null;
    mission: { id: number; mission_number: string } | null;
    reported_by: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Defects', href: '/defects' }];

export default function Index({ defects }: { defects: DefectItem[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Aircraft defects" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Aircraft defects" description="FR-DEF-001 defect sources, severity and aircraft serviceability impacts." actions={<Button asChild><Link href="/defects/create"><Plus />Report defect</Link></Button>} />

                <div className="overflow-hidden rounded-lg border bg-card text-card-foreground shadow-xs">
                    <div className="grid grid-cols-12 border-b bg-muted/50 px-4 py-3 text-xs font-medium uppercase text-muted-foreground">
                        <span className="col-span-5 md:col-span-3">Defect</span>
                        <span className="col-span-3 hidden md:block">Aircraft</span>
                        <span className="col-span-2">Severity</span>
                        <span className="col-span-2">Impact</span>
                        <span className="col-span-3 md:col-span-2">Status</span>
                    </div>
                    {defects.length === 0 ? (
                        <EmptyState icon={AlertTriangle} title="No defects reported" description="Report aircraft, mission, inspection or maintenance defects when they affect operational readiness." action={<Link href="/defects/create">Report defect</Link>} />
                    ) : (
                        defects.map((defect) => (
                            <div key={defect.id} className="grid grid-cols-12 items-center border-b px-4 py-3 text-sm last:border-b-0">
                                <div className="col-span-5 md:col-span-3">
                                    <div className="font-medium">{defect.defect_number}</div>
                                    <div className="text-muted-foreground">{defect.title}</div>
                                </div>
                                <div className="col-span-3 hidden text-muted-foreground md:block">{defect.aircraft?.registration ?? 'No aircraft'}</div>
                                <div className="col-span-2"><StatusBadge value={defect.severity.replaceAll('_', ' ')} /></div>
                                <div className="col-span-2"><StatusBadge value={defect.serviceability_impact.replaceAll('_', ' ')} /></div>
                                <div className="col-span-3 md:col-span-2"><StatusBadge value={defect.status} /></div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </AppLayout>
    );
}