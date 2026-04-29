import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as OutcomeController from '@/actions/App/Http/Controllers/Admin/MicrocurricularLearningOutcomeController';
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
    BreadcrumbItem,
    Competency,
    Faculty,
    MicrocurricularLearningOutcome,
    PaginatedResponse,
    Program,
    ProblematicNucleus,
} from '@/types';

type Props = {
    outcomes: PaginatedResponse<MicrocurricularLearningOutcome>;
    faculties: Pick<Faculty, 'id' | 'name'>[];
    programs: Pick<Program, 'id' | 'name'>[];
    nuclei: Pick<ProblematicNucleus, 'id' | 'name'>[];
    competencies: Pick<Competency, 'id' | 'name'>[];
    academicSpaces: Pick<AcademicSpace, 'id' | 'name'>[];
    filters: {
        search?: string;
        faculty_id?: string;
        program_id?: string;
        problematic_nucleus_id?: string;
        competency_id?: string;
        academic_space_id?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Resultados Microcurriculares',
        href: OutcomeController.index.url(),
    },
];

export default function MicrocurricularOutcomesIndex({
    outcomes,
    faculties,
    programs,
    nuclei,
    competencies,
    academicSpaces,
    filters,
}: Props) {
    const [toggleTarget, setToggleTarget] =
        useState<MicrocurricularLearningOutcome | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            OutcomeController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<MicrocurricularLearningOutcome, unknown>[] = [
        {
            accessorKey: 'code',
            header: 'Código',
            cell: ({ row }) => (
                <span className="font-mono text-sm font-semibold">
                    {row.original.code}
                </span>
            ),
        },
        {
            accessorKey: 'description',
            header: 'Descripción',
            cell: ({ row }) => (
                <span
                    className="block max-w-sm truncate"
                    title={row.original.description}
                >
                    {row.original.description}
                </span>
            ),
        },
        {
            id: 'type',
            header: 'Tipo',
            cell: ({ row }) => row.original.type?.name ?? '—',
        },
        {
            id: 'space',
            header: 'Espacio Académico',
            cell: ({ row }) => {
                const name = row.original.academic_space?.name ?? '—';
                return (
                    <span className="block max-w-xs truncate" title={name}>
                        {name}
                    </span>
                );
            },
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
                <div className="flex w-24 shrink-0 items-center justify-end gap-1">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={OutcomeController.show.url(row.original)}>
                            <Eye className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={OutcomeController.edit.url(row.original)}>
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
            <Head title="Resultados Microcurriculares" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Resultados Microcurriculares"
                    description="Resultados de aprendizaje por espacio académico"
                >
                    <Button asChild>
                        <Link href={OutcomeController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo resultado
                        </Link>
                    </Button>
                </PageHeader>
                <div className="flex flex-wrap items-center gap-2">
                    <Select
                        value={filters.faculty_id ?? ''}
                        onValueChange={(val) =>
                            router.get(
                                OutcomeController.index.url(),
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
                                router.get(
                                    OutcomeController.index.url(),
                                    {
                                        faculty_id: filters.faculty_id,
                                        ...(val ? { program_id: val } : {}),
                                    },
                                    { preserveState: true },
                                )
                            }
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Todos los programas" />
                            </SelectTrigger>
                            <SelectContent>
                                {programs.map((p) => (
                                    <SelectItem key={p.id} value={String(p.id)}>
                                        {p.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                    {filters.program_id && (
                        <Select
                            value={filters.problematic_nucleus_id ?? ''}
                            onValueChange={(val) =>
                                router.get(
                                    OutcomeController.index.url(),
                                    {
                                        faculty_id: filters.faculty_id,
                                        program_id: filters.program_id,
                                        ...(val
                                            ? { problematic_nucleus_id: val }
                                            : {}),
                                    },
                                    { preserveState: true },
                                )
                            }
                        >
                            <SelectTrigger className="w-56">
                                <SelectValue placeholder="Todos los núcleos" />
                            </SelectTrigger>
                            <SelectContent>
                                {nuclei.map((n) => (
                                    <SelectItem key={n.id} value={String(n.id)}>
                                        {n.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                    {filters.problematic_nucleus_id && (
                        <Select
                            value={filters.competency_id ?? ''}
                            onValueChange={(val) =>
                                router.get(
                                    OutcomeController.index.url(),
                                    {
                                        faculty_id: filters.faculty_id,
                                        program_id: filters.program_id,
                                        problematic_nucleus_id:
                                            filters.problematic_nucleus_id,
                                        ...(val ? { competency_id: val } : {}),
                                    },
                                    { preserveState: true },
                                )
                            }
                        >
                            <SelectTrigger className="w-56">
                                <SelectValue placeholder="Todas las competencias" />
                            </SelectTrigger>
                            <SelectContent>
                                {competencies.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                    {filters.competency_id && (
                        <Select
                            value={filters.academic_space_id ?? ''}
                            onValueChange={(val) =>
                                router.get(
                                    OutcomeController.index.url(),
                                    {
                                        faculty_id: filters.faculty_id,
                                        program_id: filters.program_id,
                                        problematic_nucleus_id:
                                            filters.problematic_nucleus_id,
                                        competency_id: filters.competency_id,
                                        ...(val
                                            ? { academic_space_id: val }
                                            : {}),
                                    },
                                    { preserveState: true },
                                )
                            }
                        >
                            <SelectTrigger className="w-56">
                                <SelectValue placeholder="Todos los espacios" />
                            </SelectTrigger>
                            <SelectContent>
                                {academicSpaces.map((s) => (
                                    <SelectItem key={s.id} value={String(s.id)}>
                                        {s.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                    {(filters.faculty_id ||
                        filters.program_id ||
                        filters.problematic_nucleus_id ||
                        filters.competency_id ||
                        filters.academic_space_id) && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                router.get(
                                    OutcomeController.index.url(),
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
                    data={outcomes}
                    columns={columns}
                    filters={filters}
                    searchPlaceholder="Buscar por descripción..."
                />
            </div>
            <ConfirmDialog
                open={!!toggleTarget}
                onOpenChange={(open) => {
                    if (!open) setToggleTarget(null);
                }}
                title={
                    toggleTarget?.is_active
                        ? 'Desactivar resultado'
                        : 'Activar resultado'
                }
                description={`¿Confirmas ${toggleTarget?.is_active ? 'desactivar' : 'activar'} este resultado microcurricular?`}
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
