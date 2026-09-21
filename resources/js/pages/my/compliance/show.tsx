import { MetricCard } from '@/components/uas/metric-card';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ClipboardCheck, FileWarning, Gauge, NotebookTabs } from 'lucide-react';
import { MyPilotWorkspace } from '../pilot/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Pilot', href: '/my/pilot' },
    { title: 'My Compliance', href: '/my/compliance' },
];

export default function Show({ workspace }: { workspace: MyPilotWorkspace }) {
    const { pilot, compliance, logbook, documents } = workspace;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Compliance" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="My compliance"
                    description={`Pilot-owned compliance snapshot for ${pilot.display_name}.`}
                    actions={
                        <Button variant="outline" asChild>
                            <Link href="/my/pilot">Back to pilot workspace</Link>
                        </Button>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard title="Profile status" value={compliance.profile_status.replace('_', ' ')} status={compliance.profile_status} icon={Gauge} />
                    <MetricCard title="RPC category" value={compliance.rpc_category.replace('_', ' ')} status="RPC" icon={ClipboardCheck} />
                    <MetricCard title="Expiring in 30 days" value={compliance.expiring_certificates_30_days.toString()} status={compliance.expiring_certificates_30_days > 0 ? 'Attention' : 'Clear'} icon={FileWarning} />
                    <MetricCard title="Logbook hours" value={logbook.total_hours.toString()} status={`${logbook.entry_count} entries`} icon={NotebookTabs} />
                </div>

                <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
                    <h2 className="text-sm font-semibold uppercase tracking-normal text-muted-foreground">Verified readiness</h2>
                    <p className="mt-2 text-sm text-muted-foreground">Medical and radiotelephony states shown here are operational verification states. They cannot be changed from pilot self-service.</p>
                    <div className="mt-4 grid gap-3 text-sm text-muted-foreground md:grid-cols-2">
                        <p>Medical status: {compliance.medical_status.replace('_', ' ')}</p>
                        <p>Radiotelephony: {compliance.radiotelephony_qualification.replace('_', ' ')}</p>
                        <p>Certificates linked: {compliance.certificate_count}</p>
                        <p>Documents linked: {documents.count}</p>
                    </div>
                    <p className="mt-4 text-sm text-muted-foreground">{documents.architecture_gap}</p>
                </section>
            </div>
        </AppLayout>
    );
}
