import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ReceiptText } from 'lucide-react';
import { RegulatoryFeeListItem } from './types';

export default function Index({ fees }: { fees: RegulatoryFeeListItem[] }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Fees', href: '/regulatory-fees' }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Regulatory fees" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Regulatory fees" description="Version-controlled tariff register with effective dates and source references." actions={<Button asChild><Link href="/regulatory-fees/create">New Fee</Link></Button>} />{fees.length === 0 ? <EmptyState icon={ReceiptText} title="No regulatory fees" description="Capture the first transaction fee with source and effective date." action={<Link href="/regulatory-fees/create">New Fee</Link>} /> : <div className="overflow-hidden rounded-lg border"><table className="w-full text-sm"><thead className="bg-muted text-left"><tr><th className="p-3">Transaction</th><th className="p-3">Amount</th><th className="p-3">Source</th><th className="p-3">Status</th></tr></thead><tbody>{fees.map((fee) => <tr key={fee.id} className="border-t"><td className="p-3"><Link href={`/regulatory-fees/${fee.id}`} className="font-medium hover:underline">{fee.transaction_code} / {fee.description}</Link><p className="mt-1 text-xs text-muted-foreground">{fee.regulation_part}</p></td><td className="p-3 text-muted-foreground">{fee.amount ?? 'TBC'} {fee.currency}</td><td className="p-3 text-muted-foreground">{fee.source_version} / from {fee.effective_from || 'not set'}</td><td className="p-3"><StatusBadge value={fee.status} /></td></tr>)}</tbody></table></div>}</div></AppLayout>;
}
