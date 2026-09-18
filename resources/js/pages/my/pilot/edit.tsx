import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import MyPilotForm from './form';
import { PilotOptions, PilotProfile } from './types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Pilot', href: '/my/pilot' },
    { title: 'Edit', href: '/my/pilot/edit' },
];

export default function Edit({ pilot, options }: { pilot: PilotProfile; options: PilotOptions }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit My Pilot Profile" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Edit pilot profile" description="Update personal pilot details. Regulated approval state remains controlled by authorised administrators." />
                <MyPilotForm pilot={pilot} options={options} />
            </div>
        </AppLayout>
    );
}
