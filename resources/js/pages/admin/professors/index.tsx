import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Power, UserCheck, UserX } from 'lucide-react';
import { useState } from 'react';
import * as ProfessorController from '@/actions/App/Http/Controllers/Admin/ProfessorController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem, PaginatedResponse, Professor } from '@/types';

type Props = {
    professors: PaginatedResponse<Professor>;
    filters: { search?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Profesores', href: ProfessorController.index.url() },
];

export default function ProfessorsIndex({ professors, filters }: Props) {
    const [toggleTarget, setToggleTarget] = useState<Professor | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            ProfessorController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<Professor, unknown>[] = [
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
        {
            accessorKey: 'institutional_email',
            header: 'Correo institucional',
        },
        {
            id: 'user_access',
            header: 'Acceso',
            cell: ({ row }) =>
                row.original.user_id ? (
                    <Badge
                        variant="outline"
                        className="gap-1 border-green-300 text-green-700"
                    >
                        <UserCheck className="h-3 w-3" />
                        Con cuenta
                    </Badge>
                ) : (
                    <Badge
                        variant="outline"
                        className="gap-1 text-muted-foreground"
                    >
                        <UserX className="h-3 w-3" />
                        Sin cuenta
                    </Badge>
                ),
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
                        <Link href={ProfessorController.edit.url(row.original)}>
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
            <Head title="Profesores" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Profesores"
                    description="Gestión del equipo docente"
                >
                    <Button asChild>
                        <Link href={ProfessorController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo profesor
                        </Link>
                    </Button>
                </PageHeader>
                <DataTable
                    data={professors}
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
                        ? 'Desactivar profesor'
                        : 'Activar profesor'
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
