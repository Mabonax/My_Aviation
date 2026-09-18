import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { RegulatoryFormListItem } from './types';

export default function Index({ forms }: { forms: RegulatoryFormListItem[] }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Forms', href: '/regulatory-forms' }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="SACAA form register" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="SACAA form register" description="Version-controlled regulatory form catalogue for Part 71, Part 101, Part 47 and related transactions." actions={<Button asChild><Link href="/regulatory-forms/create">New Form</Link></Button>} />{forms.length === 0 ? <EmptyState icon={FileText} title="No regulatory forms" description="Capture the first form code, revision, source and required transaction." action={<Link href="/regulatory-forms/create">New Form</Link>} /> : <div className="overflow-hidden rounded-lg border"><table className="w-full text-sm"><thead className="bg-muted text-left"><tr><th className="p-3">Form</th><th className="p-3">Transaction</th><th className="p-3">Status</th><th className="p-3">Version</th></tr></thead><tbody>{forms.map((form) => <tr key={form.id} className="border-t"><td className="p-3"><Link href={`/regulatory-forms/${form.id}`} className="font-medium hover:underline">{form.form_code} / {form.form_title}</Link><p className="mt-1 text-xs text-muted-foreground">{form.regulatory_area}</p></td><td className="p-3 text-muted-foreground">{form.required_transaction}</td><td className="p-3"><StatusBadge value={form.status} /></td><td className="p-3 text-muted-foreground">{form.revision} / effective {form.effective_date || 'not set'} / {form.superseding_versions_count} newer versions</td></tr>)}</tbody></table></div>}</div></AppLayout>;
}
