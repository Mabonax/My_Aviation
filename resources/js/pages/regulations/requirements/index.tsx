import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Scale } from 'lucide-react';
import { RegulatoryRequirementListItem } from './types';

export default function Index({ requirements }: { requirements: RegulatoryRequirementListItem[] }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Regulations', href: '/regulatory-requirements' }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Regulatory requirements" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Regulatory requirements" description="Source-controlled requirement register with version awareness." actions={<Button asChild><Link href="/regulatory-requirements/create">New Requirement</Link></Button>} />{requirements.length === 0 ? <EmptyState icon={Scale} title="No requirements" description="Capture the first source-controlled regulatory or organisational requirement." action={<Link href="/regulatory-requirements/create">New Requirement</Link>} /> : <div className="overflow-hidden rounded-lg border"><table className="w-full text-sm"><thead className="bg-muted text-left"><tr><th className="p-3">Requirement</th><th className="p-3">Source</th><th className="p-3">Status</th><th className="p-3">Version Links</th></tr></thead><tbody>{requirements.map((requirement) => <tr key={requirement.id} className="border-t"><td className="p-3"><Link href={`/regulatory-requirements/${requirement.id}`} className="font-medium hover:underline">{requirement.requirement_id} / {requirement.title}</Link><p className="mt-1 text-xs text-muted-foreground">{requirement.regulation_part}{requirement.clause_reference ? ` / ${requirement.clause_reference}` : ''}</p></td><td className="p-3 text-muted-foreground">{requirement.source_version} / effective {requirement.effective_date || 'not set'}</td><td className="p-3"><StatusBadge value={requirement.status} /></td><td className="p-3 text-muted-foreground">{requirement.training_links_count} training links / {requirement.superseding_versions_count} superseding versions</td></tr>)}</tbody></table></div>}</div></AppLayout>;
}
