import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as PeriodController from '@/actions/App/Http/Controllers/Admin/AcademicPeriodController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    AcademicPeriod,
    BreadcrumbItem,
    PaginatedResponse,
} from '@/types';

type Props = {
    periods: PaginatedResponse<AcademicPeriod>;
    filters: { search?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Períodos Académicos', href: PeriodController.index.url() },
];

export default function AcademicPeriodsIndex({ periods, filters }: Props) {
    const [toggleTarget, setToggleTarget] = useState<AcademicPeriod | null>(
        null,
    );
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            PeriodController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<AcademicPeriod, unknown>[] = [
        {
            accessorKey: 'name',
            header: 'Nombre',
            cell: ({ row }) => (
                <span className="font-mono font-semibold">
                    {row.original.name}
                </span>
            ),
        },
        {
            accessorKey: 'description',
            header: 'Descripción',
            cell: ({ row }) => row.original.description ?? '—',
        },
        {
            id: 'dates',
            header: 'Fechas',
            cell: ({ row }) => {
                const { start_date, end_date } = row.original;
                if (!start_date && !end_date) return '—';
                return `${start_date ?? '?'} → ${end_date ?? '?'}`;
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
                <div className="flex items-center justify-end gap-1">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={PeriodController.edit.url(row.original)}>
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
            <Head title="Períodos Académicos" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Períodos Académicos"
                    description="Catálogo de períodos académicos controlados"
                >
                    <Button asChild>
                        <Link href={PeriodController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo período
                        </Link>
                    </Button>
                </PageHeader>
                <DataTable
                    data={periods}
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
                        ? 'Desactivar período'
                        : 'Activar período'
                }
                description={`¿Confirmas ${toggleTarget?.is_active ? 'desactivar' : 'activar'} el período "${toggleTarget?.name}"?`}
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
