import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as OutcomeController from '@/actions/App/Http/Controllers/Admin/MesocurricularLearningOutcomeController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    BreadcrumbItem,
    Competency,
    MesocurricularLearningOutcome,
    PaginatedResponse,
} from '@/types';

type Props = {
    outcomes: PaginatedResponse<MesocurricularLearningOutcome>;
    competencies: Pick<Competency, 'id' | 'name'>[];
    filters: { search?: string; competency_id?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Resultados Mesocurriculares',
        href: OutcomeController.index.url(),
    },
];

export default function MesocurricularOutcomesIndex({
    outcomes,
    competencies,
    filters,
}: Props) {
    const [toggleTarget, setToggleTarget] =
        useState<MesocurricularLearningOutcome | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            OutcomeController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<MesocurricularLearningOutcome, unknown>[] = [
        {
            accessorKey: 'description',
            header: 'Descripción',
            cell: ({ row }) => (
                <span className="line-clamp-2 max-w-md">
                    {row.original.description}
                </span>
            ),
        },
        {
            id: 'competency',
            header: 'Competencia',
            cell: ({ row }) => row.original.competency?.name ?? '—',
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
                        <Link href={OutcomeController.edit.url(row.original)}>
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
            <Head title="Resultados Mesocurriculares" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Resultados Mesocurriculares"
                    description="Resultados de aprendizaje por competencia"
                >
                    <Button asChild>
                        <Link href={OutcomeController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo resultado
                        </Link>
                    </Button>
                </PageHeader>
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant={
                            !filters.competency_id ? 'secondary' : 'outline'
                        }
                        size="sm"
                        onClick={() =>
                            router.get(
                                OutcomeController.index.url(),
                                {},
                                { preserveState: true },
                            )
                        }
                    >
                        Todas las competencias
                    </Button>
                    {competencies.map((c) => (
                        <Button
                            key={c.id}
                            size="sm"
                            variant={
                                filters.competency_id === String(c.id)
                                    ? 'secondary'
                                    : 'outline'
                            }
                            onClick={() =>
                                router.get(
                                    OutcomeController.index.url(),
                                    { competency_id: c.id },
                                    { preserveState: true },
                                )
                            }
                        >
                            {c.name}
                        </Button>
                    ))}
                </div>
                <DataTable
                    data={outcomes}
                    columns={columns}
                    filters={filters}
                    searchPlaceholder="Buscar por descripción..."
                />
            </div>
            <ConfirmDialog
                open={!!toggleTarget}
                onOpenChange={(open) => {
                    if (!open) setToggleTarget(null);
                }}
                title={
                    toggleTarget?.is_active
                        ? 'Desactivar resultado'
                        : 'Activar resultado'
                }
                description={`¿Confirmas ${toggleTarget?.is_active ? 'desactivar' : 'activar'} este resultado mesocurricular?`}
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
