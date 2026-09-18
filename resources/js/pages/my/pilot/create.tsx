import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import MyPilotForm from './form';
import { PilotOptions } from './types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Pilot', href: '/my/pilot' },
    { title: 'Create', href: '/my/pilot/create' },
];

export default function Create({ options }: { options: PilotOptions }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Pilot Profile" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Create pilot profile" description="Set up your regulated pilot identity for compliance, missions and future mobile access." />
                <MyPilotForm options={options} />
            </div>
        </AppLayout>
    );
}
