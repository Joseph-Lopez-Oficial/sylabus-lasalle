import { Head, Link, router } from '@inertiajs/react';
import { Info, Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as CriterionController from '@/actions/App/Http/Controllers/Admin/EvaluationCriterionController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem } from '@/types';

type OutcomeType = { id: number; name: string };

type Criterion = {
    id: number;
    name: string;
    description: string | null;
    order: number;
    is_active: boolean;
    microcurricular_learning_outcome_type_id: number;
    outcome_type: OutcomeType | null;
    grades_count: number;
};

type Props = {
    criteria: Criterion[];
    types: OutcomeType[];
    filters: {
        search?: string;
        microcurricular_learning_outcome_type_id?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Criterios de Evaluación', href: CriterionController.index.url() },
];

export default function EvaluationCriteriaIndex({
    criteria,
    types,
    filters,
}: Props) {
    const [toggleTarget, setToggleTarget] = useState<Criterion | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            CriterionController.toggleStatus.url(toggleTarget),
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

    // Grouped by type so each block mirrors how the grading screen presents them.
    const byType = types
        .map((type) => ({
            type,
            items: criteria.filter(
                (c) => c.microcurricular_learning_outcome_type_id === type.id,
            ),
        }))
        .filter((group) => group.items.length > 0);

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Criterios de Evaluación" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Criterios de Evaluación"
                    description="Criterios con los que se valora cada tipo de resultado de aprendizaje"
                >
                    <Button asChild>
                        <Link href={CriterionController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo criterio
                        </Link>
                    </Button>
                </PageHeader>

                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        Un criterio nuevo solo se exige en las programaciones que
                        aún no tienen calificaciones. Los grupos ya calificados
                        conservan los criterios con los que se evaluaron.
                    </AlertDescription>
                </Alert>

                <div className="flex flex-wrap items-end gap-2">
                    <div className="flex flex-col gap-0.5">
                        <p className="text-xs font-medium text-muted-foreground">
                            Tipo de resultado
                        </p>
                        <Select
                            value={
                                filters.microcurricular_learning_outcome_type_id ??
                                ''
                            }
                            onValueChange={(val) =>
                                router.get(
                                    CriterionController.index.url(),
                                    val
                                        ? {
                                              microcurricular_learning_outcome_type_id:
                                                  val,
                                          }
                                        : {},
                                    { preserveState: true },
                                )
                            }
                        >
                            <SelectTrigger className="w-56 overflow-hidden">
                                <SelectValue
                                    placeholder="Todos los tipos"
                                    className="truncate"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {types.length === 0 ? (
                                    <SelectItem value="__empty__" disabled>
                                        Sin opciones disponibles
                                    </SelectItem>
                                ) : (
                                    types.map((t) => (
                                        <SelectItem
                                            key={t.id}
                                            value={String(t.id)}
                                        >
                                            <span className="truncate">
                                                {t.name}
                                            </span>
                                        </SelectItem>
                                    ))
                                )}
                            </SelectContent>
                        </Select>
                    </div>
                    {filters.microcurricular_learning_outcome_type_id && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                router.get(
                                    CriterionController.index.url(),
                                    {},
                                    { preserveState: true },
                                )
                            }
                        >
                            Limpiar filtros
                        </Button>
                    )}
                </div>

                {byType.length === 0 ? (
                    <Card>
                        <CardContent className="pt-6">
                            <EmptyState />
                        </CardContent>
                    </Card>
                ) : (
                    byType.map(({ type, items }) => (
                        <Card key={type.id}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    {type.name}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-20">
                                                Orden
                                            </TableHead>
                                            <TableHead>Criterio</TableHead>
                                            <TableHead className="w-40">
                                                Calificaciones
                                            </TableHead>
                                            <TableHead className="w-28">
                                                Estado
                                            </TableHead>
                                            <TableHead className="w-24" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {items.map((criterion) => (
                                            <TableRow key={criterion.id}>
                                                <TableCell className="font-mono text-sm">
                                                    {criterion.order}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-medium">
                                                        {criterion.name}
                                                    </span>
                                                    {criterion.description && (
                                                        <p className="mt-0.5 max-w-xl truncate text-xs text-muted-foreground">
                                                            {
                                                                criterion.description
                                                            }
                                                        </p>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {criterion.grades_count}
                                                </TableCell>
                                                <TableCell>
                                                    <StatusBadge
                                                        isActive={
                                                            criterion.is_active
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex w-24 shrink-0 items-center justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={CriterionController.edit.url(
                                                                    criterion,
                                                                )}
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                setToggleTarget(
                                                                    criterion,
                                                                )
                                                            }
                                                        >
                                                            <Power className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>

            <ConfirmDialog
                open={!!toggleTarget}
                onOpenChange={(open) => {
                    if (!open) setToggleTarget(null);
                }}
                title={
                    toggleTarget?.is_active
                        ? 'Desactivar criterio'
                        : 'Activar criterio'
                }
                description={
                    toggleTarget?.is_active && toggleTarget.grades_count > 0
                        ? `Este criterio tiene ${toggleTarget.grades_count} calificación(es) asociadas y no puede desactivarse.`
                        : `¿Confirmas ${toggleTarget?.is_active ? 'desactivar' : 'activar'} "${toggleTarget?.name}"?`
                }
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
