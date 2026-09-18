import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ManualDistributionOptions, OperationsManualRevision } from '../../types';
import ManualDistributionForm from './manual-distribution-form';

export default function Create({ manualRevision, options }: { manualRevision: OperationsManualRevision; options: ManualDistributionOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Operators', href: '/operators' },
        { title: manualRevision.operator.legal_entity, href: `/operators/${manualRevision.operator.id}` },
        { title: manualRevision.revision_code, href: `/operations-manual-revisions/${manualRevision.id}` },
        { title: 'New recipient', href: `/operations-manual-revisions/${manualRevision.id}/distributions/create` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New manual recipient" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="New manual recipient" description={`${manualRevision.manual_name} ${manualRevision.revision_code}`} />
                <ManualDistributionForm manualRevision={manualRevision} options={options} />
            </div>
        </AppLayout>
    );
}
