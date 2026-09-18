import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { CertificateCaseOptions, OperatorProfile } from '../types';
import CertificateCaseForm from './certificate-case-form';

export default function Create({ operator, options }: { operator: OperatorProfile; options: CertificateCaseOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Operators', href: '/operators' }, { title: operator.legal_entity, href: `/operators/${operator.id}` }, { title: 'Certificate case', href: `/operators/${operator.id}/certificate-cases/create` }];
    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Create certificate case" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Create certificate case" description={`${operator.legal_entity} / FR-OPS-002 lifecycle case`} /><section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><CertificateCaseForm operator={operator} options={options} /></section></div></AppLayout>;
}