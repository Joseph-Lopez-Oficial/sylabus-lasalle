import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as NucleusController from '@/actions/App/Http/Controllers/Admin/ProblematicNucleusController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    BreadcrumbItem,
    PaginatedResponse,
    Program,
    ProblematicNucleus,
} from '@/types';

type Props = {
    nuclei: PaginatedResponse<ProblematicNucleus>;
    programs: Pick<Program, 'id' | 'name'>[];
    filters: { search?: string; program_id?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Núcleos Problemáticos', href: NucleusController.index.url() },
];

export default function ProblematicNucleiIndex({
    nuclei,
    programs,
    filters,
}: Props) {
    const [toggleTarget, setToggleTarget] = useState<ProblematicNucleus | null>(
        null,
    );
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            NucleusController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<ProblematicNucleus, unknown>[] = [
        { accessorKey: 'name', header: 'Nombre' },
        {
            id: 'program',
            header: 'Programa',
            cell: ({ row }) => row.original.program?.name ?? '—',
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
                        <Link href={NucleusController.edit.url(row.original)}>
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
            <Head title="Núcleos Problemáticos" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Núcleos Problemáticos"
                    description="Gestión de núcleos problemáticos por programa"
                >
                    <Button asChild>
                        <Link href={NucleusController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo núcleo
                        </Link>
                    </Button>
                </PageHeader>

                <div className="flex flex-wrap gap-2">
                    <Button
                        variant={!filters.program_id ? 'secondary' : 'outline'}
                        size="sm"
                        onClick={() =>
                            router.get(
                                NucleusController.index.url(),
                                {},
                                { preserveState: true },
                            )
                        }
                    >
                        Todos los programas
                    </Button>
                    {programs.map((p) => (
                        <Button
                            key={p.id}
                            size="sm"
                            variant={
                                filters.program_id === String(p.id)
                                    ? 'secondary'
                                    : 'outline'
                            }
                            onClick={() =>
                                router.get(
                                    NucleusController.index.url(),
                                    { program_id: p.id },
                                    { preserveState: true },
                                )
                            }
                        >
                            {p.name}
                        </Button>
                    ))}
                </div>

                <DataTable
                    data={nuclei}
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
                        ? 'Desactivar núcleo'
                        : 'Activar núcleo'
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
