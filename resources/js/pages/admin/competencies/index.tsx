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
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    BreadcrumbItem,
    Competency,
    PaginatedResponse,
    ProblematicNucleus,
} from '@/types';

type Props = {
    competencies: PaginatedResponse<Competency>;
    nuclei: Pick<ProblematicNucleus, 'id' | 'name'>[];
    filters: { search?: string; problematic_nucleus_id?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competencias', href: CompetencyController.index.url() },
];

export default function CompetenciesIndex({
    competencies,
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
        { accessorKey: 'name', header: 'Nombre' },
        {
            id: 'nucleus',
            header: 'Núcleo Problemático',
            cell: ({ row }) => row.original.problematic_nucleus?.name ?? '—',
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
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant={
                            !filters.problematic_nucleus_id
                                ? 'secondary'
                                : 'outline'
                        }
                        size="sm"
                        onClick={() =>
                            router.get(
                                CompetencyController.index.url(),
                                {},
                                { preserveState: true },
                            )
                        }
                    >
                        Todos los núcleos
                    </Button>
                    {nuclei.map((n) => (
                        <Button
                            key={n.id}
                            size="sm"
                            variant={
                                filters.problematic_nucleus_id === String(n.id)
                                    ? 'secondary'
                                    : 'outline'
                            }
                            onClick={() =>
                                router.get(
                                    CompetencyController.index.url(),
                                    { problematic_nucleus_id: n.id },
                                    { preserveState: true },
                                )
                            }
                        >
                            {n.name}
                        </Button>
                    ))}
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
