import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import FormRegisterForm from './form';
import { RegulatoryForm } from './types';

export default function Supersede({ form }: { form: RegulatoryForm }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Forms', href: '/regulatory-forms' },
        { title: form.form_code, href: `/regulatory-forms/${form.id}` },
        { title: 'Create Version', href: `/regulatory-forms/${form.id}/supersede` },
    ];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Create regulatory form version" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Create regulatory form version" description={`${form.form_code} / ${form.revision}`} /><FormRegisterForm form={form} superseding /></div></AppLayout>;
}
