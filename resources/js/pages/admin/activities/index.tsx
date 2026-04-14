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
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    Activity,
    BreadcrumbItem,
    PaginatedResponse,
    Topic,
} from '@/types';

type Props = {
    activities: PaginatedResponse<Activity>;
    topics: Pick<Topic, 'id' | 'name'>[];
    filters: { search?: string; topic_id?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Actividades', href: ActivityController.index.url() },
];

export default function ActivitiesIndex({
    activities,
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
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant={!filters.topic_id ? 'secondary' : 'outline'}
                        size="sm"
                        onClick={() =>
                            router.get(
                                ActivityController.index.url(),
                                {},
                                { preserveState: true },
                            )
                        }
                    >
                        Todos los temas
                    </Button>
                    {topics.map((t) => (
                        <Button
                            key={t.id}
                            size="sm"
                            variant={
                                filters.topic_id === String(t.id)
                                    ? 'secondary'
                                    : 'outline'
                            }
                            onClick={() =>
                                router.get(
                                    ActivityController.index.url(),
                                    { topic_id: t.id },
                                    { preserveState: true },
                                )
                            }
                        >
                            {t.name}
                        </Button>
                    ))}
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
