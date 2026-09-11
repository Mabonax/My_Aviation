import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import PilotForm from './pilot-form';
import { PilotOptions } from './types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pilots', href: '/pilots' },
    { title: 'Create', href: '/pilots/create' },
];

export default function Create({ options }: { options: PilotOptions }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create pilot profile" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Create pilot profile" description="Capture the FR-PIL-001 master record before certificate compliance evaluation is added." />
                <PilotForm options={options} />
            </div>
        </AppLayout>
    );
}
