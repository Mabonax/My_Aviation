import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { AlertTriangle, BatteryCharging, Building2, ClipboardCheck, FileArchive, GraduationCap, LayoutGrid, Map, Plane, Scale, UserCircle } from 'lucide-react';
import AppLogo from './app-logo';
import { OperatorWorkspaceSwitcher } from './operator-workspace-switcher';

interface ExperienceNavigationItem {
    key: string;
    label: string;
    path: string;
}

interface Experience {
    persona: string;
    navigation: ExperienceNavigationItem[];
}

const iconFor = (key: string) => {
    const icons = {
        home: LayoutGrid,
        pilot: UserCircle,
        operators: Building2,
        operator: Building2,
        missions: Map,
        aircraft: Plane,
        compliance: ClipboardCheck,
        evidence: FileArchive,
        training: GraduationCap,
        regulations: Scale,
        batteries: BatteryCharging,
        defects: AlertTriangle,
    } as const;

    return icons[key as keyof typeof icons] ?? LayoutGrid;
};

export function AppSidebar() {
    const { experience } = usePage<{ experience: Experience | null }>().props;

    const mainNavItems: NavItem[] = (experience?.navigation ?? [
        { key: 'home', label: 'Dashboard', path: '/dashboard' },
    ]).map((item) => ({
        title: item.label,
        url: item.path,
        icon: iconFor(item.key),
    }));

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

            <OperatorWorkspaceSwitcher />

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
