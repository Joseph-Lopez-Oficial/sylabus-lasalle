import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    Activity,
    BreadcrumbItem,
    PaginatedResponse,
    Product,
} from '@/types';

type Props = {
    products: PaginatedResponse<Product>;
    activities: Pick<Activity, 'id' | 'name'>[];
    filters: { search?: string; activity_id?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Productos', href: ProductController.index.url() },
];

export default function ProductsIndex({
    products,
    activities,
    filters,
}: Props) {
    const [toggleTarget, setToggleTarget] = useState<Product | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            ProductController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<Product, unknown>[] = [
        { accessorKey: 'order', header: '#' },
        { accessorKey: 'name', header: 'Nombre' },
        {
            id: 'activity',
            header: 'Actividad',
            cell: ({ row }) => row.original.activity?.name ?? '—',
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
                        <Link href={ProductController.edit.url(row.original)}>
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
            <Head title="Productos" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Productos"
                    description="Productos de aprendizaje por actividad"
                >
                    <Button asChild>
                        <Link href={ProductController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo producto
                        </Link>
                    </Button>
                </PageHeader>
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant={!filters.activity_id ? 'secondary' : 'outline'}
                        size="sm"
                        onClick={() =>
                            router.get(
                                ProductController.index.url(),
                                {},
                                { preserveState: true },
                            )
                        }
                    >
                        Todas las actividades
                    </Button>
                    {activities.map((a) => (
                        <Button
                            key={a.id}
                            size="sm"
                            variant={
                                filters.activity_id === String(a.id)
                                    ? 'secondary'
                                    : 'outline'
                            }
                            onClick={() =>
                                router.get(
                                    ProductController.index.url(),
                                    { activity_id: a.id },
                                    { preserveState: true },
                                )
                            }
                        >
                            {a.name}
                        </Button>
                    ))}
                </div>
                <DataTable
                    data={products}
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
                        ? 'Desactivar producto'
                        : 'Activar producto'
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
