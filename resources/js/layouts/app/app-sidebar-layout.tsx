import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';

type UiCapabilities = { pilot_self_service?: boolean; platform_admin?: boolean } | null;

export default function AppSidebarLayout({ children, breadcrumbs = [] }: { children: React.ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    const { url, props } = usePage<{ uiCapabilities?: UiCapabilities }>();
    const pilotDashboard = url.startsWith('/dashboard') && !!props.uiCapabilities?.pilot_self_service && !props.uiCapabilities?.platform_admin;

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className={pilotDashboard ? 'overflow-hidden bg-[#f4f9fd]' : undefined}>
                {!pilotDashboard && <AppSidebarHeader breadcrumbs={breadcrumbs} />}
                {children}
            </AppContent>
        </AppShell>
    );
}
