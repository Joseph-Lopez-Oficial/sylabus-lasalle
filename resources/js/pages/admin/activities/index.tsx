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
    competencies: Pick<Competency, 'id' | 'code' | 'name'>[];
    academicSpaces: Pick<AcademicSpace, 'id' | 'code' | 'name'>[];
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
                <div className="flex flex-wrap items-end gap-2">
                    <div className="flex flex-col gap-0.5">
                        <p className="text-xs font-medium text-muted-foreground">
                            Facultad
                        </p>
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
                            <SelectTrigger className="w-48 overflow-hidden">
                                <SelectValue
                                    placeholder="Todas las facultades"
                                    className="truncate"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {faculties.length === 0 ? (
                                    <SelectItem value="__empty__" disabled>
                                        Sin opciones disponibles
                                    </SelectItem>
                                ) : (
                                    faculties.map((f) => (
                                        <SelectItem
                                            key={f.id}
                                            value={String(f.id)}
                                        >
                                            <span className="truncate">
                                                {f.name}
                                            </span>
                                        </SelectItem>
                                    ))
                                )}
                            </SelectContent>
                        </Select>
                    </div>
                    {filters.faculty_id && (
                        <div className="flex flex-col gap-0.5">
                            <p className="text-xs font-medium text-muted-foreground">
                                Programa
                            </p>
                            <Select
                                value={filters.program_id ?? ''}
                                onValueChange={(val) =>
                                    router.get(
                                        ActivityController.index.url(),
                                        {
                                            faculty_id: filters.faculty_id,
                                            ...(val ? { program_id: val } : {}),
                                        },
                                        { preserveState: true },
                                    )
                                }
                            >
                                <SelectTrigger className="w-48 overflow-hidden">
                                    <SelectValue
                                        placeholder="Todos los programas"
                                        className="truncate"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {programs.length === 0 ? (
                                        <SelectItem value="__empty__" disabled>
                                            Sin opciones disponibles
                                        </SelectItem>
                                    ) : (
                                        programs.map((p) => (
                                            <SelectItem
                                                key={p.id}
                                                value={String(p.id)}
                                            >
                                                <span className="truncate">
                                                    {p.name}
                                                </span>
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                    {filters.program_id && (
                        <div className="flex flex-col gap-0.5">
                            <p className="text-xs font-medium text-muted-foreground">
                                Núcleo Problémico
                            </p>
                            <Select
                                value={filters.problematic_nucleus_id ?? ''}
                                onValueChange={(val) =>
                                    router.get(
                                        ActivityController.index.url(),
                                        {
                                            faculty_id: filters.faculty_id,
                                            program_id: filters.program_id,
                                            ...(val
                                                ? {
                                                      problematic_nucleus_id:
                                                          val,
                                                  }
                                                : {}),
                                        },
                                        { preserveState: true },
                                    )
                                }
                            >
                                <SelectTrigger className="w-72 overflow-hidden">
                                    <SelectValue
                                        placeholder="Todos los núcleos"
                                        className="truncate"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {nuclei.length === 0 ? (
                                        <SelectItem value="__empty__" disabled>
                                            Sin opciones disponibles
                                        </SelectItem>
                                    ) : (
                                        nuclei.map((n) => (
                                            <SelectItem
                                                key={n.id}
                                                value={String(n.id)}
                                            >
                                                <span className="truncate">
                                                    {n.name}
                                                </span>
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                    {filters.problematic_nucleus_id && (
                        <div className="flex flex-col gap-0.5">
                            <p className="text-xs font-medium text-muted-foreground">
                                Competencia
                            </p>
                            <Select
                                value={filters.competency_id ?? ''}
                                onValueChange={(val) =>
                                    router.get(
                                        ActivityController.index.url(),
                                        {
                                            faculty_id: filters.faculty_id,
                                            program_id: filters.program_id,
                                            problematic_nucleus_id:
                                                filters.problematic_nucleus_id,
                                            ...(val
                                                ? { competency_id: val }
                                                : {}),
                                        },
                                        { preserveState: true },
                                    )
                                }
                            >
                                <SelectTrigger className="w-72 overflow-hidden">
                                    <SelectValue
                                        placeholder="Todas las competencias"
                                        className="truncate"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {competencies.length === 0 ? (
                                        <SelectItem value="__empty__" disabled>
                                            Sin opciones disponibles
                                        </SelectItem>
                                    ) : (
                                        competencies.map((c) => (
                                            <SelectItem
                                                key={c.id}
                                                value={String(c.id)}
                                            >
                                                <span className="flex min-w-0 gap-1.5">
                                                    <span className="shrink-0 font-mono text-xs text-muted-foreground">
                                                        {c.code}
                                                    </span>
                                                    <span className="truncate">
                                                        {c.name}
                                                    </span>
                                                </span>
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                    {filters.competency_id && (
                        <div className="flex flex-col gap-0.5">
                            <p className="text-xs font-medium text-muted-foreground">
                                Espacio Académico
                            </p>
                            <Select
                                value={filters.academic_space_id ?? ''}
                                onValueChange={(val) =>
                                    router.get(
                                        ActivityController.index.url(),
                                        {
                                            faculty_id: filters.faculty_id,
                                            program_id: filters.program_id,
                                            problematic_nucleus_id:
                                                filters.problematic_nucleus_id,
                                            competency_id:
                                                filters.competency_id,
                                            ...(val
                                                ? { academic_space_id: val }
                                                : {}),
                                        },
                                        { preserveState: true },
                                    )
                                }
                            >
                                <SelectTrigger className="w-72 overflow-hidden">
                                    <SelectValue
                                        placeholder="Todos los espacios"
                                        className="truncate"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {academicSpaces.length === 0 ? (
                                        <SelectItem value="__empty__" disabled>
                                            Sin opciones disponibles
                                        </SelectItem>
                                    ) : (
                                        academicSpaces.map((s) => (
                                            <SelectItem
                                                key={s.id}
                                                value={String(s.id)}
                                            >
                                                <span className="flex min-w-0 gap-1.5">
                                                    <span className="shrink-0 font-mono text-xs text-muted-foreground">
                                                        {s.code}
                                                    </span>
                                                    <span className="truncate">
                                                        {s.name}
                                                    </span>
                                                </span>
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                    {filters.academic_space_id && (
                        <div className="flex flex-col gap-0.5">
                            <p className="text-xs font-medium text-muted-foreground">
                                Tema
                            </p>
                            <Select
                                value={filters.topic_id ?? ''}
                                onValueChange={(val) =>
                                    router.get(
                                        ActivityController.index.url(),
                                        {
                                            faculty_id: filters.faculty_id,
                                            program_id: filters.program_id,
                                            problematic_nucleus_id:
                                                filters.problematic_nucleus_id,
                                            competency_id:
                                                filters.competency_id,
                                            academic_space_id:
                                                filters.academic_space_id,
                                            ...(val ? { topic_id: val } : {}),
                                        },
                                        { preserveState: true },
                                    )
                                }
                            >
                                <SelectTrigger className="w-48 overflow-hidden">
                                    <SelectValue
                                        placeholder="Todos los temas"
                                        className="truncate"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {topics.length === 0 ? (
                                        <SelectItem value="__empty__" disabled>
                                            Sin opciones disponibles
                                        </SelectItem>
                                    ) : (
                                        topics.map((t) => (
                                            <SelectItem
                                                key={t.id}
                                                value={String(t.id)}
                                            >
                                                <span className="truncate">
                                                    {t.name}
                                                </span>
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                        </div>
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
