import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as ActivityController from '@/actions/App/Http/Controllers/Admin/ActivityController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    AcademicSpace,
    Activity,
    BreadcrumbItem,
    Competency,
    Faculty,
    PaginatedResponse,
    Program,
    ProblematicNucleus,
    Topic,
} from '@/types';

type Props = {
    activities: PaginatedResponse<Activity>;
    faculties: Pick<Faculty, 'id' | 'name'>[];
    programs: Pick<Program, 'id' | 'name'>[];
    nuclei: Pick<ProblematicNucleus, 'id' | 'name'>[];
    competencies: Pick<Competency, 'id' | 'name'>[];
    academicSpaces: Pick<AcademicSpace, 'id' | 'name'>[];
    topics: Pick<Topic, 'id' | 'name'>[];
    filters: {
        search?: string;
        faculty_id?: string;
        program_id?: string;
        problematic_nucleus_id?: string;
        competency_id?: string;
        academic_space_id?: string;
        topic_id?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Actividades', href: ActivityController.index.url() },
];

export default function ActivitiesIndex({
    activities,
    faculties,
    programs,
    nuclei,
    competencies,
    academicSpaces,
    topics,
    filters,
}: Props) {
    const [toggleTarget, setToggleTarget] = useState<Activity | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            ActivityController.toggleStatus.url(toggleTarget),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setToggling(false);
                    setToggleTarget(null);
                },
            },
        );
    }

    const columns: ColumnDef<Activity, unknown>[] = [
        { accessorKey: 'order', header: '#' },
        { accessorKey: 'name', header: 'Nombre' },
        {
            id: 'type',
            header: 'Tipo',
            cell: ({ row }) => row.original.activity_type?.name ?? '—',
        },
        {
            id: 'topic',
            header: 'Tema',
            cell: ({ row }) => row.original.topic?.name ?? '—',
        },
        {
            accessorKey: 'is_active',
            header: 'Estado',
            cell: ({ row }) => (
                <StatusBadge isActive={row.original.is_active} />
            ),
        },
        {
            id: 'actions',
            header: '',
            cell: ({ row }) => (
                <div className="flex items-center justify-end gap-1">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={ActivityController.edit.url(row.original)}>
                            <Pencil className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => setToggleTarget(row.original)}
                    >
                        <Power className="h-4 w-4" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Actividades" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Actividades"
                    description="Actividades de aprendizaje por tema"
                >
                    <Button asChild>
                        <Link href={ActivityController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva actividad
                        </Link>
                    </Button>
                </PageHeader>
                <div className="flex flex-wrap items-center gap-2">
                    <Select
                        value={filters.faculty_id ?? ''}
                        onValueChange={(val) =>
                            router.get(
                                ActivityController.index.url(),
                                val ? { faculty_id: val } : {},
                                { preserveState: true },
                            )
                        }
                    >
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="Todas las facultades" />
                        </SelectTrigger>
                        <SelectContent>
                            {faculties.map((f) => (
                                <SelectItem key={f.id} value={String(f.id)}>
                                    {f.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {filters.faculty_id && (
                        <Select
                            value={filters.program_id ?? ''}
                            onValueChange={(val) =>
                                router.get(ActivityController.index.url(), { faculty_id: filters.faculty_id, ...(val ? { program_id: val } : {}) }, { preserveState: true })
                            }
                        >
                            <SelectTrigger className="w-48"><SelectValue placeholder="Todos los programas" /></SelectTrigger>
                            <SelectContent>{programs.map((p) => <SelectItem key={p.id} value={String(p.id)}>{p.name}</SelectItem>)}</SelectContent>
                        </Select>
                    )}
                    {filters.program_id && (
                        <Select
                            value={filters.problematic_nucleus_id ?? ''}
                            onValueChange={(val) =>
                                router.get(ActivityController.index.url(), { faculty_id: filters.faculty_id, program_id: filters.program_id, ...(val ? { problematic_nucleus_id: val } : {}) }, { preserveState: true })
                            }
                        >
                            <SelectTrigger className="w-56"><SelectValue placeholder="Todos los núcleos" /></SelectTrigger>
                            <SelectContent>{nuclei.map((n) => <SelectItem key={n.id} value={String(n.id)}>{n.name}</SelectItem>)}</SelectContent>
                        </Select>
                    )}
                    {filters.problematic_nucleus_id && (
                        <Select
                            value={filters.competency_id ?? ''}
                            onValueChange={(val) =>
                                router.get(ActivityController.index.url(), { faculty_id: filters.faculty_id, program_id: filters.program_id, problematic_nucleus_id: filters.problematic_nucleus_id, ...(val ? { competency_id: val } : {}) }, { preserveState: true })
                            }
                        >
                            <SelectTrigger className="w-56"><SelectValue placeholder="Todas las competencias" /></SelectTrigger>
                            <SelectContent>{competencies.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}</SelectContent>
                        </Select>
                    )}
                    {filters.competency_id && (
                        <Select
                            value={filters.academic_space_id ?? ''}
                            onValueChange={(val) =>
                                router.get(ActivityController.index.url(), { faculty_id: filters.faculty_id, program_id: filters.program_id, problematic_nucleus_id: filters.problematic_nucleus_id, competency_id: filters.competency_id, ...(val ? { academic_space_id: val } : {}) }, { preserveState: true })
                            }
                        >
                            <SelectTrigger className="w-56"><SelectValue placeholder="Todos los espacios" /></SelectTrigger>
                            <SelectContent>{academicSpaces.map((s) => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}</SelectContent>
                        </Select>
                    )}
                    {filters.academic_space_id && (
                        <Select
                            value={filters.topic_id ?? ''}
                            onValueChange={(val) =>
                                router.get(ActivityController.index.url(), { faculty_id: filters.faculty_id, program_id: filters.program_id, problematic_nucleus_id: filters.problematic_nucleus_id, competency_id: filters.competency_id, academic_space_id: filters.academic_space_id, ...(val ? { topic_id: val } : {}) }, { preserveState: true })
                            }
                        >
                            <SelectTrigger className="w-48"><SelectValue placeholder="Todos los temas" /></SelectTrigger>
                            <SelectContent>{topics.map((t) => <SelectItem key={t.id} value={String(t.id)}>{t.name}</SelectItem>)}</SelectContent>
                        </Select>
                    )}
                    {(filters.faculty_id ||
                        filters.program_id ||
                        filters.problematic_nucleus_id ||
                        filters.competency_id ||
                        filters.academic_space_id ||
                        filters.topic_id) && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                router.get(
                                    ActivityController.index.url(),
                                    {},
                                    { preserveState: true },
                                )
                            }
                        >
                            Limpiar filtros
                        </Button>
                    )}
                </div>
                <DataTable
                    data={activities}
                    columns={columns}
                    filters={filters}
                    searchPlaceholder="Buscar por nombre..."
                />
            </div>
            <ConfirmDialog
                open={!!toggleTarget}
                onOpenChange={(open) => {
                    if (!open) setToggleTarget(null);
                }}
                title={
                    toggleTarget?.is_active
                        ? 'Desactivar actividad'
                        : 'Activar actividad'
                }
                description={`¿Confirmas ${toggleTarget?.is_active ? 'desactivar' : 'activar'} "${toggleTarget?.name}"?`}
                confirmLabel={
                    toggleTarget?.is_active ? 'Desactivar' : 'Activar'
                }
                variant={toggleTarget?.is_active ? 'destructive' : 'default'}
                loading={toggling}
                onConfirm={handleToggle}
            />
        </AdminLayout>
    );
}
