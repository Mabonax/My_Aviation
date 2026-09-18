import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { ExternalIntegrationListItem } from './types';

export default function Index({ integrations }: { integrations: ExternalIntegrationListItem[] }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'External integrations', href: '/regulatory-external-integrations' }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="External regulatory integrations" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="External regulatory integrations" description="Manual, document-based, verified external and API-classified authority processes." actions={<Button asChild><Link href="/regulatory-external-integrations/create">New Integration</Link></Button>} />{integrations.length === 0 ? <EmptyState icon={ExternalLink} title="No external integrations" description="Capture the first authority process and evidence workflow." action={<Link href="/regulatory-external-integrations/create">New Integration</Link>} /> : <div className="overflow-hidden rounded-lg border"><table className="w-full text-sm"><thead className="bg-muted text-left"><tr><th className="p-3">Integration</th><th className="p-3">Authority</th><th className="p-3">Classification</th><th className="p-3">Status</th></tr></thead><tbody>{integrations.map((integration) => <tr key={integration.id} className="border-t"><td className="p-3"><Link href={`/regulatory-external-integrations/${integration.id}`} className="font-medium hover:underline">{integration.name}</Link><p className="mt-1 text-xs text-muted-foreground">{integration.regulatory_area} / {integration.supported_process}</p></td><td className="p-3 text-muted-foreground">{integration.authority}</td><td className="p-3 text-muted-foreground">{integration.classification.replaceAll('_', ' ')} / API assumption {integration.api_assumption_blocked ? 'blocked' : 'allowed'}</td><td className="p-3"><StatusBadge value={integration.status} /></td></tr>)}</tbody></table></div>}</div></AppLayout>;
}
