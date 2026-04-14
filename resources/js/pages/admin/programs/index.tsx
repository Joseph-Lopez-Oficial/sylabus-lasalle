import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as ProgramController from '@/actions/App/Http/Controllers/Admin/ProgramController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    BreadcrumbItem,
    Faculty,
    PaginatedResponse,
    Program,
} from '@/types';

type Props = {
    programs: PaginatedResponse<Program>;
    faculties: Pick<Faculty, 'id' | 'name'>[];
    filters: { search?: string; faculty_id?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Programas', href: ProgramController.index.url() },
];

export default function ProgramsIndex({ programs, faculties, filters }: Props) {
    const [toggleTarget, setToggleTarget] = useState<Program | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            ProgramController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<Program, unknown>[] = [
        {
            accessorKey: 'code',
            header: 'Código',
            cell: ({ row }) => (
                <span className="font-mono text-sm">{row.original.code}</span>
            ),
        },
        {
            accessorKey: 'name',
            header: 'Programa',
        },
        {
            id: 'faculty',
            header: 'Facultad',
            cell: ({ row }) => row.original.faculty?.name ?? '—',
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
                        <Link href={ProgramController.edit.url(row.original)}>
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
            <Head title="Programas" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Programas"
                    description="Gestión de programas académicos"
                >
                    <Button asChild>
                        <Link href={ProgramController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo programa
                        </Link>
                    </Button>
                </PageHeader>

                {/* Faculty filter */}
                <div className="flex gap-2">
                    <Button
                        variant={!filters.faculty_id ? 'secondary' : 'outline'}
                        size="sm"
                        onClick={() =>
                            router.get(
                                ProgramController.index.url(),
                                {},
                                { preserveState: true },
                            )
                        }
                    >
                        Todas las facultades
                    </Button>
                    {faculties.map((f) => (
                        <Button
                            key={f.id}
                            variant={
                                filters.faculty_id === String(f.id)
                                    ? 'secondary'
                                    : 'outline'
                            }
                            size="sm"
                            onClick={() =>
                                router.get(
                                    ProgramController.index.url(),
                                    { faculty_id: f.id },
                                    { preserveState: true },
                                )
                            }
                        >
                            {f.name}
                        </Button>
                    ))}
                </div>

                <DataTable
                    data={programs}
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
                        ? 'Desactivar programa'
                        : 'Activar programa'
                }
                description={`¿Confirmas ${toggleTarget?.is_active ? 'desactivar' : 'activar'} el programa "${toggleTarget?.name}"?`}
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
