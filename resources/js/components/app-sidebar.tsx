import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { AlertTriangle, BatteryCharging, Bell, BookOpen, Building2, ClipboardCheck, ExternalLink, FileArchive, FileText, Folder, GraduationCap, LayoutGrid, Map, Network, Plane, ReceiptText, Scale, ShieldCheck, UserCircle, UserRound } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
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

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        url: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        url: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
