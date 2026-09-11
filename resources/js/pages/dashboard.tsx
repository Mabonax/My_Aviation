import { Button } from '@/components/ui/button';
import { MetricCard } from '@/components/uas/metric-card';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, FileWarning, Plane, ShieldCheck, UserRound } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardSummary {
    pilots: number;
    aircraft: number;
    certificates_expiring_30_days: number;
    open_findings: number;
    documents_missing_review: number;
}

export default function Dashboard({ summary }: { summary: DashboardSummary }) {
    const phaseItems = [
        { title: 'Pilot profiles', value: summary.pilots.toString(), status: 'Implemented', icon: UserRound },
        { title: 'Aircraft records', value: summary.aircraft.toString(), status: 'Implemented', icon: Plane },
        { title: 'Expiring in 30 days', value: summary.certificates_expiring_30_days.toString(), status: summary.certificates_expiring_30_days > 0 ? 'Attention' : 'Valid', icon: AlertTriangle },
        { title: 'Open findings', value: summary.open_findings.toString(), status: summary.open_findings > 0 ? 'Attention' : 'Valid', icon: FileWarning },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Phase 1 compliance workspace"
                    description="Pilot and fleet master records for the first VMT UAS compliance release."
                    actions={
                        <>
                            <Button asChild>
                                <Link href="/pilots">
                                    Open pilots
                                    <ArrowRight />
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href="/phase-1/verification">
                                    Verify Phase 1
                                    <ShieldCheck />
                                </Link>
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {phaseItems.map((item) => (
                        <MetricCard key={item.title} title={item.title} value={item.value} status={item.status} icon={item.icon} />
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
