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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    BreadcrumbItem,
    Faculty,
    PaginatedResponse,
    Program,
    ProblematicNucleus,
} from '@/types';

type Props = {
    nuclei: PaginatedResponse<ProblematicNucleus>;
    faculties: Pick<Faculty, 'id' | 'name'>[];
    programs: Pick<Program, 'id' | 'name'>[];
    filters: { search?: string; faculty_id?: string; program_id?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Núcleos Problémicos', href: NucleusController.index.url() },
];

export default function ProblematicNucleiIndex({
    nuclei,
    faculties,
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
        {
            accessorKey: 'name',
            header: 'Nombre',
            cell: ({ row }) => (
                <span
                    className="block max-w-xs truncate"
                    title={row.original.name}
                >
                    {row.original.name}
                </span>
            ),
        },
        {
            id: 'program',
            header: 'Programa',
            cell: ({ row }) => {
                const name = row.original.program?.name ?? '—';
                return (
                    <span className="block max-w-xs truncate" title={name}>
                        {name}
                    </span>
                );
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
                <div className="flex w-24 shrink-0 items-center justify-end gap-1">
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
            <Head title="Núcleos Problémicos" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Núcleos Problémicos"
                    description="Gestión de núcleos problemáticos por programa"
                >
                    <Button asChild>
                        <Link href={NucleusController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo núcleo
                        </Link>
                    </Button>
                </PageHeader>

                <div className="flex flex-wrap items-end gap-2">
                    <div className="flex flex-col gap-0.5">
                        <p className="text-xs font-medium text-muted-foreground">
                            Facultad
                        </p>
                        <Select
                            value={filters.faculty_id ?? ''}
                            onValueChange={(val) =>
                                router.get(
                                    NucleusController.index.url(),
                                    val ? { faculty_id: val } : {},
                                    { preserveState: true },
                                )
                            }
                        >
                            <SelectTrigger className="w-48 overflow-hidden">
                                <SelectValue
                                    placeholder="Todas las facultades"
                                    className="truncate"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {faculties.length === 0 ? (
                                    <SelectItem value="__empty__" disabled>
                                        Sin opciones disponibles
                                    </SelectItem>
                                ) : (
                                    faculties.map((f) => (
                                        <SelectItem
                                            key={f.id}
                                            value={String(f.id)}
                                        >
                                            <span className="truncate">
                                                {f.name}
                                            </span>
                                        </SelectItem>
                                    ))
                                )}
                            </SelectContent>
                        </Select>
                    </div>
                    {filters.faculty_id && (
                        <div className="flex flex-col gap-0.5">
                            <p className="text-xs font-medium text-muted-foreground">
                                Programa
                            </p>
                            <Select
                                value={filters.program_id ?? ''}
                                onValueChange={(val) =>
                                    router.get(
                                        NucleusController.index.url(),
                                        {
                                            faculty_id: filters.faculty_id,
                                            ...(val ? { program_id: val } : {}),
                                        },
                                        { preserveState: true },
                                    )
                                }
                            >
                                <SelectTrigger className="w-48 overflow-hidden">
                                    <SelectValue
                                        placeholder="Todos los programas"
                                        className="truncate"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {programs.length === 0 ? (
                                        <SelectItem value="__empty__" disabled>
                                            Sin opciones disponibles
                                        </SelectItem>
                                    ) : (
                                        programs.map((p) => (
                                            <SelectItem
                                                key={p.id}
                                                value={String(p.id)}
                                            >
                                                <span className="truncate">
                                                    {p.name}
                                                </span>
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                    {(filters.faculty_id || filters.program_id) && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                router.get(
                                    NucleusController.index.url(),
                                    {},
                                    { preserveState: true },
                                )
                            }
                        >
                            Limpiar filtros
                        </Button>
                    )}
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
