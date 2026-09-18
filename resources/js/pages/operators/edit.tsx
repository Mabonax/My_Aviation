import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import OperatorForm from './operator-form';
import { OperatorOptions, OperatorProfile } from './types';

export default function Edit({ operator, options }: { operator: OperatorProfile; options: OperatorOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Operators', href: '/operators' }, { title: operator.legal_entity, href: `/operators/${operator.id}` }, { title: 'Edit', href: `/operators/${operator.id}/edit` }];
    return <AppLayout breadcrumbs={breadcrumbs}><Head title={`Edit ${operator.legal_entity}`} /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title={`Edit ${operator.legal_entity}`} description="Update the controlled FR-OPS-001 operator governance profile." /><section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><OperatorForm options={options} operator={operator} /></section></div></AppLayout>;
}