import { Head, Link } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    GraduationCap,
    LayoutGrid,
    Users,
} from 'lucide-react';
import * as SpaceController from '@/actions/App/Http/Controllers/Admin/AcademicSpaceController';
import * as FacultyController from '@/actions/App/Http/Controllers/Admin/FacultyController';
import * as ProfessorController from '@/actions/App/Http/Controllers/Admin/ProfessorController';
import * as ProgrammingController from '@/actions/App/Http/Controllers/Admin/ProgrammingController';
import * as StudentController from '@/actions/App/Http/Controllers/Admin/StudentController';
import { PageHeader } from '@/components/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem } from '@/types';

type Metrics = {
    faculties: number;
    academic_spaces: number;
    professors: number;
    students: number;
    programmings: number;
};

type Props = { metrics?: Metrics };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
];

export default function AdminDashboard({ metrics }: Props) {
    const cards = [
        {
            title: 'Facultades',
            value: metrics?.faculties ?? '—',
            description: 'Unidades académicas registradas',
            icon: Building2,
            href: FacultyController.index.url(),
        },
        {
            title: 'Espacios Académicos',
            value: metrics?.academic_spaces ?? '—',
            description: 'Asignaturas con diseño microcurricular',
            icon: BookOpen,
            href: SpaceController.index.url(),
        },
        {
            title: 'Profesores',
            value: metrics?.professors ?? '—',
            description: 'Docentes activos en el sistema',
            icon: Users,
            href: ProfessorController.index.url(),
        },
        {
            title: 'Estudiantes',
            value: metrics?.students ?? '—',
            description: 'Estudiantes registrados',
            icon: GraduationCap,
            href: StudentController.index.url(),
        },
        {
            title: 'Programaciones',
            value: metrics?.programmings ?? '—',
            description: 'Secciones académicas activas',
            icon: LayoutGrid,
            href: ProgrammingController.index.url(),
        },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Panel de Administración"
                    description="Resumen general del sistema de diseño pedagógico universitario"
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    {cards.map((card) => (
                        <Link key={card.title} href={card.href} prefetch>
                            <Card className="h-full cursor-pointer transition-shadow hover:shadow-md">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">
                                        {card.title}
                                    </CardTitle>
                                    <card.icon className="h-4 w-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold">
                                        {card.value}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {card.description}
                                    </p>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
