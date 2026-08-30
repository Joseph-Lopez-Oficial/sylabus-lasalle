import { Head, Link, router } from '@inertiajs/react';
import { Info, Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import * as PerformanceLevelController from '@/actions/App/Http/Controllers/Admin/PerformanceLevelController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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

type Level = {
    id: number;
    name: string;
    description: string | null;
    order: number;
    grade_value: number | null;
    is_below_basic_threshold: boolean;
    is_active: boolean;
    grades_count: number;
};

type Props = { levels: Level[] };

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Niveles de Desempeño',
        href: PerformanceLevelController.index.url(),
    },
];

export default function PerformanceLevelsIndex({ levels }: Props) {
    const [toggleTarget, setToggleTarget] = useState<Level | null>(null);
    const [toggling, setToggling] = useState(false);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            PerformanceLevelController.toggleStatus.url(toggleTarget),
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

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Niveles de Desempeño" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Niveles de Desempeño"
                    description="Escala de calificación institucional aplicada en todo el sistema"
                >
                    <Button asChild>
                        <Link href={PerformanceLevelController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo nivel
                        </Link>
                    </Button>
                </PageHeader>

                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        El valor de cada nivel es la nota con la que se calculan
                        los promedios. Modificar un valor recalcula las
                        estadísticas de todas las programaciones que lo usan.
                    </AlertDescription>
                </Alert>

                <Card>
                    <CardContent className="pt-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-20">
                                        Orden
                                    </TableHead>
                                    <TableHead>Nivel</TableHead>
                                    <TableHead className="w-28">
                                        Valor
                                    </TableHead>
                                    <TableHead className="w-44">
                                        Calificaciones
                                    </TableHead>
                                    <TableHead className="w-28">
                                        Estado
                                    </TableHead>
                                    <TableHead className="w-24" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {levels.map((level) => (
                                    <TableRow key={level.id}>
                                        <TableCell className="font-mono text-sm">
                                            {level.order}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium">
                                                    {level.name}
                                                </span>
                                                {level.is_below_basic_threshold && (
                                                    <Badge variant="secondary">
                                                        Umbral de bajo
                                                        rendimiento
                                                    </Badge>
                                                )}
                                            </div>
                                            {level.description && (
                                                <p className="mt-0.5 max-w-xl truncate text-xs text-muted-foreground">
                                                    {level.description}
                                                </p>
                                            )}
                                        </TableCell>
                                        <TableCell className="font-mono font-semibold">
                                            {level.grade_value === null ? (
                                                <span className="text-muted-foreground">
                                                    Sin valor
                                                </span>
                                            ) : (
                                                level.grade_value
                                            )}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {level.grades_count}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                isActive={level.is_active}
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
                                                        href={PerformanceLevelController.edit.url(
                                                            level,
                                                        )}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        setToggleTarget(level)
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
            </div>

            <ConfirmDialog
                open={!!toggleTarget}
                onOpenChange={(open) => {
                    if (!open) setToggleTarget(null);
                }}
                title={
                    toggleTarget?.is_active
                        ? 'Desactivar nivel'
                        : 'Activar nivel'
                }
                description={toggleDescription(toggleTarget)}
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

function toggleDescription(level: Level | null): string {
    if (!level?.is_active) {
        return `¿Confirmas activar "${level?.name}"?`;
    }

    if (level.grades_count > 0) {
        return `Este nivel tiene ${level.grades_count} calificación(es) asociadas y no puede desactivarse.`;
    }

    if (level.is_below_basic_threshold) {
        return 'Este nivel define el umbral de bajo rendimiento y no puede desactivarse. Marque otro nivel como umbral primero.';
    }

    return `¿Confirmas desactivar "${level.name}"?`;
}
