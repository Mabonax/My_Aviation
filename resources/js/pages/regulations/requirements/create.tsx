import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import RequirementForm from './requirement-form';

export default function Create() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Regulations', href: '/regulatory-requirements' }, { title: 'New Requirement', href: '/regulatory-requirements/create' }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="New regulatory requirement" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="New regulatory requirement" description="Capture source, applicability, evidence, frequency, validity and retention controls." /><RequirementForm /></div></AppLayout>;
}
