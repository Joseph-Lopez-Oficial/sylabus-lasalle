import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as SpaceController from '@/actions/App/Http/Controllers/Admin/AcademicSpaceController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    AcademicSpace,
    BreadcrumbItem,
    Competency,
    PaginatedResponse,
} from '@/types';

type Props = {
    academicSpaces: PaginatedResponse<AcademicSpace>;
    competencies: Pick<Competency, 'id' | 'name'>[];
    filters: { search?: string; competency_id?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Espacios Académicos', href: SpaceController.index.url() },
];

export default function AcademicSpacesIndex({
    academicSpaces,
    competencies,
    filters,
}: Props) {
    const [toggleTarget, setToggleTarget] = useState<AcademicSpace | null>(
        null,
    );
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            SpaceController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<AcademicSpace, unknown>[] = [
        {
            accessorKey: 'code',
            header: 'Código',
            cell: ({ row }) => (
                <span className="font-mono text-sm">{row.original.code}</span>
            ),
        },
        { accessorKey: 'name', header: 'Nombre' },
        {
            accessorKey: 'credits',
            header: 'Créditos',
            cell: ({ row }) => (
                <span className="text-center">{row.original.credits}</span>
            ),
        },
        {
            id: 'competency',
            header: 'Competencia',
            cell: ({ row }) => row.original.competency?.name ?? '—',
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
                        <Link href={SpaceController.show.url(row.original)}>
                            <Eye className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={SpaceController.edit.url(row.original)}>
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
            <Head title="Espacios Académicos" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Espacios Académicos"
                    description="Asignaturas con su diseño microcurricular"
                >
                    <Button asChild>
                        <Link href={SpaceController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo espacio
                        </Link>
                    </Button>
                </PageHeader>
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant={
                            !filters.competency_id ? 'secondary' : 'outline'
                        }
                        size="sm"
                        onClick={() =>
                            router.get(
                                SpaceController.index.url(),
                                {},
                                { preserveState: true },
                            )
                        }
                    >
                        Todas las competencias
                    </Button>
                    {competencies.map((c) => (
                        <Button
                            key={c.id}
                            size="sm"
                            variant={
                                filters.competency_id === String(c.id)
                                    ? 'secondary'
                                    : 'outline'
                            }
                            onClick={() =>
                                router.get(
                                    SpaceController.index.url(),
                                    { competency_id: c.id },
                                    { preserveState: true },
                                )
                            }
                        >
                            {c.name}
                        </Button>
                    ))}
                </div>
                <DataTable
                    data={academicSpaces}
                    columns={columns}
                    filters={filters}
                    searchPlaceholder="Buscar por nombre o código..."
                />
            </div>
            <ConfirmDialog
                open={!!toggleTarget}
                onOpenChange={(open) => {
                    if (!open) setToggleTarget(null);
                }}
                title={
                    toggleTarget?.is_active
                        ? 'Desactivar espacio'
                        : 'Activar espacio'
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
