import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import FormRegisterForm from './form';

export default function Create() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Forms', href: '/regulatory-forms' }, { title: 'New Form', href: '/regulatory-forms/create' }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="New regulatory form" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="New regulatory form" description="Capture form code, revision, source reference, required transaction and verification state." /><FormRegisterForm /></div></AppLayout>;
}
