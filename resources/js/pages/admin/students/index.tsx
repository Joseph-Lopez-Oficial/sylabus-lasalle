import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as StudentController from '@/actions/App/Http/Controllers/Admin/StudentController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem, PaginatedResponse, Student } from '@/types';

type Props = {
    students: PaginatedResponse<Student>;
    filters: { search?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Estudiantes', href: StudentController.index.url() },
];

export default function StudentsIndex({ students, filters }: Props) {
    const [toggleTarget, setToggleTarget] = useState<Student | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            StudentController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<Student, unknown>[] = [
        {
            accessorKey: 'document_number',
            header: 'Documento',
            cell: ({ row }) => (
                <span className="font-mono text-sm">
                    {row.original.document_number}
                </span>
            ),
        },
        {
            id: 'full_name',
            header: 'Nombre',
            cell: ({ row }) =>
                `${row.original.first_name} ${row.original.last_name}`,
        },
        { accessorKey: 'email', header: 'Correo electrónico' },
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
                        <Link href={StudentController.edit.url(row.original)}>
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
            <Head title="Estudiantes" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Estudiantes"
                    description="Gestión de estudiantes registrados en el sistema"
                >
                    <Button asChild>
                        <Link href={StudentController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo estudiante
                        </Link>
                    </Button>
                </PageHeader>
                <DataTable
                    data={students}
                    columns={columns}
                    filters={filters}
                    searchPlaceholder="Buscar por nombre o documento..."
                />
            </div>
            <ConfirmDialog
                open={!!toggleTarget}
                onOpenChange={(open) => {
                    if (!open) setToggleTarget(null);
                }}
                title={
                    toggleTarget?.is_active
                        ? 'Desactivar estudiante'
                        : 'Activar estudiante'
                }
                description={`¿Confirmas ${toggleTarget?.is_active ? 'desactivar' : 'activar'} a "${toggleTarget?.first_name} ${toggleTarget?.last_name}"?`}
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
