import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ManualTrainingOptions, OperationsManualRevision } from '../../types';
import ManualTrainingRequirementForm from './manual-training-requirement-form';

export default function Create({ manualRevision, options }: { manualRevision: OperationsManualRevision; options: ManualTrainingOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Operators', href: '/operators' },
        { title: manualRevision.operator.legal_entity, href: `/operators/${manualRevision.operator.id}` },
        { title: manualRevision.revision_code, href: `/operations-manual-revisions/${manualRevision.id}` },
        { title: 'New training requirement', href: `/operations-manual-revisions/${manualRevision.id}/training-requirements/create` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New training requirement" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="New training requirement" description={`${manualRevision.manual_name} ${manualRevision.revision_code}`} />
                <ManualTrainingRequirementForm manualRevision={manualRevision} options={options} />
            </div>
        </AppLayout>
    );
}
