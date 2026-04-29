import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as OutcomeController from '@/actions/App/Http/Controllers/Admin/MesocurricularLearningOutcomeController';
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
    BreadcrumbItem,
    Competency,
    Faculty,
    MesocurricularLearningOutcome,
    PaginatedResponse,
    ProblematicNucleus,
    Program,
} from '@/types';

type Props = {
    outcomes: PaginatedResponse<MesocurricularLearningOutcome>;
    faculties: Pick<Faculty, 'id' | 'name'>[];
    programs: Pick<Program, 'id' | 'name'>[];
    nuclei: Pick<ProblematicNucleus, 'id' | 'name'>[];
    competencies: Pick<Competency, 'id' | 'name'>[];
    filters: {
        search?: string;
        faculty_id?: string;
        program_id?: string;
        problematic_nucleus_id?: string;
        competency_id?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Resultados Mesocurriculares',
        href: OutcomeController.index.url(),
    },
];

export default function MesocurricularOutcomesIndex({
    outcomes,
    faculties,
    programs,
    nuclei,
    competencies,
    filters,
}: Props) {
    const [toggleTarget, setToggleTarget] =
        useState<MesocurricularLearningOutcome | null>(null);
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

    const columns: ColumnDef<MesocurricularLearningOutcome, unknown>[] = [
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
            id: 'competency',
            header: 'Competencia',
            cell: ({ row }) => {
                const name = row.original.competency?.name ?? '—';
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
            <Head title="Resultados Mesocurriculares" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Resultados Mesocurriculares"
                    description="Resultados de aprendizaje por competencia"
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

                    {(filters.faculty_id ||
                        filters.program_id ||
                        filters.problematic_nucleus_id ||
                        filters.competency_id) && (
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
                description={`¿Confirmas ${toggleTarget?.is_active ? 'desactivar' : 'activar'} este resultado mesocurricular?`}
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
