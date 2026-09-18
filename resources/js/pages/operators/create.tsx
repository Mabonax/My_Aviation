import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import OperatorForm from './operator-form';
import { OperatorOptions } from './types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Operators', href: '/operators' }, { title: 'Create', href: '/operators/create' }];

export default function Create({ options }: { options: OperatorOptions }) {
    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Create operator" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Create operator" description="Capture the UAS operator profile and certificate holder governance record." /><section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><OperatorForm options={options} /></section></div></AppLayout>;
}