import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { AlertTriangle, BatteryCharging, Bell, Building2, ClipboardCheck, ExternalLink, FileArchive, FileText, GraduationCap, LayoutGrid, Map, Network, Plane, ReceiptText, Scale, ShieldCheck, UserCircle, UserRound } from 'lucide-react';
import { OperatorWorkspaceSwitcher } from './operator-workspace-switcher';

const adminNavItems: NavItem[] = [
    { title: 'Aeronautical Information', url: '/aeronautical-information', icon: Map },
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'My Pilot',
        url: '/my/pilot',
        icon: UserCircle,
    },
    {
        title: 'Operators',
        url: '/operators',
        icon: Building2,
    },
    {
        title: 'Pilots',
        url: '/pilots',
        icon: UserRound,
    },
    {
        title: 'Aircraft',
        url: '/aircraft',
        icon: Plane,
    },
    {
        title: 'Aircraft Catalogue',
        url: '/aircraft-catalogue',
        icon: FileText,
    },
    {
        title: 'Training',
        url: '/training-courses',
        icon: GraduationCap,
    },
    {
        title: 'Regulations',
        url: '/regulatory-requirements',
        icon: Scale,
    },
    {
        title: 'Forms',
        url: '/regulatory-forms',
        icon: FileText,
    },
    {
        title: 'Fees',
        url: '/regulatory-fees',
        icon: ReceiptText,
    },
    {
        title: 'External',
        url: '/regulatory-external-integrations',
        icon: ExternalLink,
    },
    {
        title: 'Compliance',
        url: '/compliance/register',
        icon: ClipboardCheck,
    },
    {
        title: 'Evidence',
        url: '/evidence-documents',
        icon: FileArchive,
    },
    {
        title: 'Traceability',
        url: '/compliance/traceability',
        icon: Network,
    },
    {
        title: 'GIS Projects',
        url: '/gis-projects',
        icon: Map,
    },
    {
        title: 'Notifications',
        url: '/compliance-notifications',
        icon: Bell,
    },
    {
        title: 'Batteries',
        url: '/batteries',
        icon: BatteryCharging,
    },
    {
        title: 'Defects',
        url: '/defects',
        icon: AlertTriangle,
    },
    {
        title: 'Phase 1 Verify',
        url: '/phase-1/verification',
        icon: ShieldCheck,
    },
];

type UiCapabilities = {
    persona: 'platform_admin' | 'pilot' | 'operator_user' | 'user';
    pilot_self_service: boolean;
    operator_workspace: boolean;
    operator_manage: boolean;
    missions: boolean;
    aeronautical_information: boolean;
    platform_admin: boolean;
};

export function AppSidebar() {
    const { uiCapabilities } = usePage<{ uiCapabilities: UiCapabilities | null }>().props;
    const items: NavItem[] = [
        ...(uiCapabilities?.aeronautical_information ? [{ title: 'Aeronautical Information', url: '/aeronautical-information', icon: Map }] : []),
        { title: 'Dashboard', url: '/dashboard', icon: LayoutGrid },
        ...(uiCapabilities?.pilot_self_service ? [{ title: 'My Pilot', url: '/my/pilot', icon: UserCircle }] : []),
        ...(uiCapabilities?.missions ? [{ title: 'Missions', url: '/missions', icon: Plane }] : []),
        ...(uiCapabilities?.platform_admin ? adminNavItems.filter((item) => !['Aeronautical Information', 'Dashboard', 'My Pilot'].includes(item.title)) : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset" className="[&_[data-sidebar=sidebar]]:border-r-0 [&_[data-sidebar=sidebar]]:bg-[#082942] [&_[data-sidebar=sidebar]]:text-white">
            <SidebarHeader className="border-b border-white/10 bg-[#082942] px-5 py-5">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="h-auto p-0 hover:bg-transparent">
                            <Link href="/dashboard" prefetch>
                                <img src="/yaw logo versions/SVG/logo horizontal on black bg.svg" alt="YAW" className="h-12 w-auto max-w-[175px] object-contain object-left" />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <p className="mt-1 pl-1 text-[8px] font-semibold uppercase tracking-[0.23em] text-slate-300 group-data-[collapsible=icon]:hidden">South African unmanned aviation<br/>to a higher standard</p>
            </SidebarHeader>

            <OperatorWorkspaceSwitcher />

            <SidebarContent className="bg-[#082942] px-2 pt-2 [&_a]:text-slate-100 [&_button]:text-slate-100">
                <NavMain items={items} />
                <div className="pointer-events-none mt-auto hidden min-h-52 flex-col justify-end overflow-hidden rounded-xl bg-[linear-gradient(to_top,rgba(4,26,43,.15),rgba(4,26,43,.8)),url('/yaw-dashboard-alpine.svg')] bg-cover bg-center p-5 md:flex group-data-[collapsible=icon]:hidden">
                    <p className="text-[12px] font-semibold uppercase tracking-[0.28em] text-white">Safe skies</p>
                    <p className="mt-1 text-[12px] font-semibold uppercase tracking-[0.28em] text-white">Enable more</p>
                </div>
            </SidebarContent>

            <SidebarFooter className="border-t border-white/10 bg-[#082942] text-white [&_button]:text-white">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
