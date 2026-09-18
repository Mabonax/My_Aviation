import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ManualRevisionOptions, OperatorProfile } from '../types';
import ManualRevisionForm from './manual-revision-form';

export default function Create({ operator, options }: { operator: OperatorProfile; options: ManualRevisionOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Operators', href: '/operators' },
        { title: operator.legal_entity, href: `/operators/${operator.id}` },
        { title: 'New manual revision', href: `/operators/${operator.id}/manual-revisions/create` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New manual revision" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="New manual revision" description={operator.legal_entity} />
                <ManualRevisionForm operator={operator} options={options} />
            </div>
        </AppLayout>
    );
}
