import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as TopicController from '@/actions/App/Http/Controllers/Admin/TopicController';
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
    Topic,
} from '@/types';

type Props = {
    topics: PaginatedResponse<Topic>;
    academicSpaces: Pick<AcademicSpace, 'id' | 'name'>[];
    filters: { search?: string; academic_space_id?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Temas', href: TopicController.index.url() },
];

export default function TopicsIndex({
    topics,
    academicSpaces,
    filters,
}: Props) {
    const [toggleTarget, setToggleTarget] = useState<Topic | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            TopicController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<Topic, unknown>[] = [
        {
            accessorKey: 'order',
            header: '#',
            cell: ({ row }) => row.original.order,
        },
        { accessorKey: 'name', header: 'Nombre' },
        {
            id: 'space',
            header: 'Espacio Académico',
            cell: ({ row }) => row.original.academic_space?.name ?? '—',
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
                        <Link href={TopicController.edit.url(row.original)}>
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
            <Head title="Temas" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Temas"
                    description="Unidades temáticas por espacio académico"
                >
                    <Button asChild>
                        <Link href={TopicController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo tema
                        </Link>
                    </Button>
                </PageHeader>
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant={
                            !filters.academic_space_id ? 'secondary' : 'outline'
                        }
                        size="sm"
                        onClick={() =>
                            router.get(
                                TopicController.index.url(),
                                {},
                                { preserveState: true },
                            )
                        }
                    >
                        Todos los espacios
                    </Button>
                    {academicSpaces.map((s) => (
                        <Button
                            key={s.id}
                            size="sm"
                            variant={
                                filters.academic_space_id === String(s.id)
                                    ? 'secondary'
                                    : 'outline'
                            }
                            onClick={() =>
                                router.get(
                                    TopicController.index.url(),
                                    { academic_space_id: s.id },
                                    { preserveState: true },
                                )
                            }
                        >
                            {s.name}
                        </Button>
                    ))}
                </div>
                <DataTable
                    data={topics}
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
                    toggleTarget?.is_active ? 'Desactivar tema' : 'Activar tema'
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
