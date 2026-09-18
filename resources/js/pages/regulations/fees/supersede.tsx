import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import FeeForm from './fee-form';
import { RegulatoryFee } from './types';

export default function Supersede({ fee }: { fee: RegulatoryFee }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Fees', href: '/regulatory-fees' },
        { title: fee.transaction_code, href: `/regulatory-fees/${fee.id}` },
        { title: 'Create Version', href: `/regulatory-fees/${fee.id}/supersede` },
    ];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Create regulatory fee version" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Create regulatory fee version" description={`${fee.transaction_code} / ${fee.source_version}`} /><FeeForm fee={fee} superseding /></div></AppLayout>;
}
