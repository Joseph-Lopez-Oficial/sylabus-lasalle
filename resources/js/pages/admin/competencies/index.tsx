import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as CompetencyController from '@/actions/App/Http/Controllers/Admin/CompetencyController';
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
    PaginatedResponse,
    Program,
    ProblematicNucleus,
} from '@/types';

type Props = {
    competencies: PaginatedResponse<Competency>;
    faculties: Pick<Faculty, 'id' | 'name'>[];
    programs: Pick<Program, 'id' | 'name'>[];
    nuclei: Pick<ProblematicNucleus, 'id' | 'name'>[];
    filters: {
        search?: string;
        faculty_id?: string;
        program_id?: string;
        problematic_nucleus_id?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competencias', href: CompetencyController.index.url() },
];

export default function CompetenciesIndex({
    competencies,
    faculties,
    programs,
    nuclei,
    filters,
}: Props) {
    const [toggleTarget, setToggleTarget] = useState<Competency | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            CompetencyController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<Competency, unknown>[] = [
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
            accessorKey: 'name',
            header: 'Nombre',
            cell: ({ row }) => (
                <span
                    className="block max-w-sm truncate"
                    title={row.original.name}
                >
                    {row.original.name}
                </span>
            ),
        },
        {
            id: 'nucleus',
            header: 'Núcleo Problémico',
            cell: ({ row }) => {
                const name = row.original.problematic_nucleus?.name ?? '—';
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
                        <Link
                            href={CompetencyController.edit.url(row.original)}
                        >
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
            <Head title="Competencias" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Competencias"
                    description="Gestión de competencias por núcleo problemático"
                >
                    <Button asChild>
                        <Link href={CompetencyController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva competencia
                        </Link>
                    </Button>
                </PageHeader>
                <div className="flex flex-wrap items-center gap-2">
                    <Select
                        value={filters.faculty_id ?? ''}
                        onValueChange={(val) =>
                            router.get(
                                CompetencyController.index.url(),
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
                                    CompetencyController.index.url(),
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
                                    CompetencyController.index.url(),
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
                    {(filters.faculty_id ||
                        filters.program_id ||
                        filters.problematic_nucleus_id) && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                router.get(
                                    CompetencyController.index.url(),
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
                    data={competencies}
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
                        ? 'Desactivar competencia'
                        : 'Activar competencia'
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
