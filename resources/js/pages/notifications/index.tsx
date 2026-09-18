import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { ComplianceNotificationListItem } from './types';

export default function Index({ notifications }: { notifications: ComplianceNotificationListItem[] }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifications', href: '/compliance-notifications' }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Compliance notifications" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Compliance notifications" description="Auditable notification register for compliance events, deadlines and regulatory changes." actions={<Button asChild><Link href="/compliance-notifications/create">New Notification</Link></Button>} />{notifications.length === 0 ? <EmptyState icon={Bell} title="No notifications" description="Plan the first compliance notification." action={<Link href="/compliance-notifications/create">New Notification</Link>} /> : <div className="overflow-hidden rounded-lg border"><table className="w-full text-sm"><thead className="bg-muted text-left"><tr><th className="p-3">Notification</th><th className="p-3">Recipient</th><th className="p-3">Channel</th><th className="p-3">Status</th></tr></thead><tbody>{notifications.map((notification) => <tr key={notification.id} className="border-t"><td className="p-3"><Link href={`/compliance-notifications/${notification.id}`} className="font-medium hover:underline">{notification.subject}</Link><p className="mt-1 text-xs text-muted-foreground">{notification.notification_type.replaceAll('_', ' ')} / due {notification.due_at || 'not set'}</p></td><td className="p-3 text-muted-foreground">{notification.recipient || 'Unassigned'}</td><td className="p-3 text-muted-foreground">{notification.channel.replaceAll('_', ' ')} / {notification.priority}</td><td className="p-3"><StatusBadge value={notification.status} /></td></tr>)}</tbody></table></div>}</div></AppLayout>;
}
