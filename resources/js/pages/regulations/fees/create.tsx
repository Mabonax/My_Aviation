import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import FeeForm from './fee-form';

export default function Create() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Fees', href: '/regulatory-fees' }, { title: 'New Fee', href: '/regulatory-fees/create' }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="New regulatory fee" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="New regulatory fee" description="Capture tariff amount, currency, effective window, source and verification state." /><FeeForm /></div></AppLayout>;
}
