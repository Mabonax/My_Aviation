import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { CertificateCaseOptions, OperatorCertificateCase } from '../types';
import CertificateCaseForm from './certificate-case-form';

export default function Edit({ certificateCase, options }: { certificateCase: OperatorCertificateCase; options: CertificateCaseOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Operators', href: '/operators' }, { title: certificateCase.operator.legal_entity, href: `/operators/${certificateCase.operator.id}` }, { title: certificateCase.case_number, href: `/operator-certificate-cases/${certificateCase.id}` }, { title: 'Edit', href: `/operator-certificate-cases/${certificateCase.id}/edit` }];
    return <AppLayout breadcrumbs={breadcrumbs}><Head title={`Edit ${certificateCase.case_number}`} /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title={`Edit ${certificateCase.case_number}`} description="Update certificate lifecycle case evidence, submission status and authority outcome." /><section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><CertificateCaseForm certificateCase={certificateCase} options={options} /></section></div></AppLayout>;
}