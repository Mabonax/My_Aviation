import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import RequirementForm from './requirement-form';
import { RegulatoryRequirement } from './types';

export default function Supersede({ requirement }: { requirement: RegulatoryRequirement }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Regulations', href: '/regulatory-requirements' },
        { title: requirement.requirement_id, href: `/regulatory-requirements/${requirement.id}` },
        { title: 'Create Version', href: `/regulatory-requirements/${requirement.id}/supersede` },
    ];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Create regulatory requirement version" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Create regulatory requirement version" description={`${requirement.requirement_id} / ${requirement.source_version}`} /><RequirementForm requirement={requirement} superseding /></div></AppLayout>;
}
