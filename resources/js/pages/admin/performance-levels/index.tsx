import { Head, Link } from '@inertiajs/react';
import { Info, Pencil } from 'lucide-react';
import * as PerformanceLevelController from '@/actions/App/Http/Controllers/Admin/PerformanceLevelController';
import { PageHeader } from '@/components/page-header';
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
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Niveles de Desempeño" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Niveles de Desempeño"
                    description="Escala de calificación institucional aplicada en todo el sistema"
                />

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
                                        <TableCell className="text-right">
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
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
