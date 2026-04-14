import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as ProgrammingController from '@/actions/App/Http/Controllers/Admin/ProgrammingController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    AcademicSpace,
    BreadcrumbItem,
    PaginatedResponse,
    Professor,
    Programming,
} from '@/types';

type Props = {
    programmings: PaginatedResponse<Programming>;
    professors: Pick<Professor, 'id' | 'first_name' | 'last_name'>[];
    academicSpaces: Pick<AcademicSpace, 'id' | 'name'>[];
    filters: {
        search?: string;
        professor_id?: string;
        academic_space_id?: string;
        period?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Programaciones', href: ProgrammingController.index.url() },
];

export default function ProgrammingsIndex({
    programmings,
    professors,
    filters,
}: Props) {
    const [toggleTarget, setToggleTarget] = useState<Programming | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            ProgrammingController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<Programming, unknown>[] = [
        {
            id: 'space',
            header: 'Espacio Académico',
            cell: ({ row }) => row.original.academic_space?.name ?? '—',
        },
        {
            id: 'professor',
            header: 'Profesor',
            cell: ({ row }) =>
                row.original.professor
                    ? `${row.original.professor.first_name} ${row.original.professor.last_name}`
                    : '—',
        },
        { accessorKey: 'period', header: 'Período' },
        {
            accessorKey: 'group',
            header: 'Grupo',
            cell: ({ row }) => row.original.group ?? '—',
        },
        {
            id: 'modality',
            header: 'Modalidad',
            cell: ({ row }) => row.original.modality?.name ?? '—',
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
                        <Link
                            href={ProgrammingController.show.url(row.original)}
                        >
                            <Eye className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button variant="ghost" size="icon" asChild>
                        <Link
                            href={ProgrammingController.edit.url(row.original)}
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
            <Head title="Programaciones" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Programaciones"
                    description="Secciones académicas por período y profesor"
                >
                    <Button asChild>
                        <Link href={ProgrammingController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva programación
                        </Link>
                    </Button>
                </PageHeader>

                {/* Filtros */}
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant={
                            !filters.professor_id ? 'secondary' : 'outline'
                        }
                        size="sm"
                        onClick={() =>
                            router.get(
                                ProgrammingController.index.url(),
                                { ...filters, professor_id: undefined },
                                { preserveState: true },
                            )
                        }
                    >
                        Todos los profesores
                    </Button>
                    {professors.map((p) => (
                        <Button
                            key={p.id}
                            size="sm"
                            variant={
                                filters.professor_id === String(p.id)
                                    ? 'secondary'
                                    : 'outline'
                            }
                            onClick={() =>
                                router.get(
                                    ProgrammingController.index.url(),
                                    { ...filters, professor_id: p.id },
                                    { preserveState: true },
                                )
                            }
                        >
                            {p.first_name} {p.last_name}
                        </Button>
                    ))}
                </div>

                <DataTable
                    data={programmings}
                    columns={columns}
                    filters={filters}
                    searchPlaceholder="Buscar por período o grupo..."
                />
            </div>
            <ConfirmDialog
                open={!!toggleTarget}
                onOpenChange={(open) => {
                    if (!open) setToggleTarget(null);
                }}
                title={
                    toggleTarget?.is_active
                        ? 'Desactivar programación'
                        : 'Activar programación'
                }
                description={`¿Confirmas ${toggleTarget?.is_active ? 'desactivar' : 'activar'} esta programación?`}
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
