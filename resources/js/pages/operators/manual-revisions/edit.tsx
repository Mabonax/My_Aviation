import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ManualRevisionOptions, OperationsManualRevision } from '../types';
import ManualRevisionForm from './manual-revision-form';

export default function Edit({ manualRevision, options }: { manualRevision: OperationsManualRevision; options: ManualRevisionOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Operators', href: '/operators' },
        { title: manualRevision.operator.legal_entity, href: `/operators/${manualRevision.operator.id}` },
        { title: manualRevision.revision_code, href: `/operations-manual-revisions/${manualRevision.id}` },
        { title: 'Edit', href: `/operations-manual-revisions/${manualRevision.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${manualRevision.revision_code}`} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Edit manual revision" description={`${manualRevision.manual_name} ${manualRevision.revision_code}`} />
                <ManualRevisionForm manualRevision={manualRevision} options={options} />
            </div>
        </AppLayout>
    );
}
