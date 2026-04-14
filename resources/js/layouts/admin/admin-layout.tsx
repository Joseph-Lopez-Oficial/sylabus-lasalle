import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    GraduationCap,
    LayoutDashboard,
    Settings,
    Users,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { BreadcrumbItem } from '@/types';
import AppLogo from '@/components/app-logo';

type Props = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

const navItems = [
    {
        group: 'Principal',
        items: [
            { title: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
        ],
    },
    {
        group: 'Estructura Académica',
        items: [
            { title: 'Facultades', href: '/admin/faculties', icon: Building2 },
            { title: 'Programas', href: '/admin/programs', icon: GraduationCap },
            { title: 'Núcleos Problemáticos', href: '/admin/problematic-nuclei', icon: BookOpen },
            { title: 'Competencias', href: '/admin/competencies', icon: BookOpen },
            { title: 'Resultados Meso.', href: '/admin/mesocurricular-outcomes', icon: BookOpen },
            { title: 'Espacios Académicos', href: '/admin/academic-spaces', icon: BookOpen },
            { title: 'Resultados Micro.', href: '/admin/microcurricular-outcomes', icon: BookOpen },
            { title: 'Temas', href: '/admin/topics', icon: BookOpen },
            { title: 'Actividades', href: '/admin/activities', icon: BookOpen },
            { title: 'Productos', href: '/admin/products', icon: BookOpen },
        ],
    },
    {
        group: 'Personas',
        items: [
            { title: 'Profesores', href: '/admin/professors', icon: Users },
            { title: 'Estudiantes', href: '/admin/students', icon: Users },
        ],
    },
    {
        group: 'Programaciones',
        items: [
            { title: 'Programaciones', href: '/admin/programmings', icon: Settings },
        ],
    },
];

function AdminSidebar() {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin/dashboard">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navItems.map((group) => (
                    <SidebarGroup key={group.group} className="px-2 py-0">
                        <SidebarGroupLabel>{group.group}</SidebarGroupLabel>
                        <SidebarMenu>
                            {group.items.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isCurrentUrl(item.href)}
                                        tooltip={{ children: item.title }}
                                    >
                                        <Link href={item.href} prefetch>
                                            <item.icon />
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

export default function AdminLayout({ children, breadcrumbs = [] }: Props) {
    return (
        <AppShell variant="sidebar">
            <AdminSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
