import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import PilotForm from './pilot-form';
import { PilotOptions, PilotProfile } from './types';

export default function Edit({ pilot, options }: { pilot: PilotProfile; options: PilotOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pilots', href: '/pilots' },
        { title: pilot.display_name, href: `/pilots/${pilot.id}` },
        { title: 'Edit', href: `/pilots/${pilot.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${pilot.display_name}`} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Edit pilot profile" description="Maintain the pilot master record without overriding derived compliance states." />
                <PilotForm pilot={pilot} options={options} />
            </div>
        </AppLayout>
    );
}
