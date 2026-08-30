import { Form, Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft } from 'lucide-react';
import * as PerformanceLevelController from '@/actions/App/Http/Controllers/Admin/PerformanceLevelController';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem } from '@/types';

type Level = {
    id: number;
    name: string;
    description: string | null;
    order: number;
    grade_value: number | null;
    is_below_basic_threshold: boolean;
};

type Props = { level: Level; gradesCount: number };

export default function PerformanceLevelsEdit({ level, gradesCount }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Niveles de Desempeño',
            href: PerformanceLevelController.index.url(),
        },
        { title: level.name, href: PerformanceLevelController.edit.url(level) },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar: ${level.name}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title={`Editar: ${level.name}`}>
                    <Button variant="outline" asChild>
                        <Link href={PerformanceLevelController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>

                {gradesCount > 0 && (
                    <Alert className="max-w-2xl border-amber-300 bg-amber-50 dark:bg-amber-950/20">
                        <AlertTriangle className="h-4 w-4 text-amber-600" />
                        <AlertDescription className="text-amber-800 dark:text-amber-200">
                            {gradesCount} calificación(es) usan este nivel. Al
                            cambiar su valor se recalculan los promedios y las
                            estadísticas que dependen de ellas.
                        </AlertDescription>
                    </Alert>
                )}

                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            action={PerformanceLevelController.update.url(
                                level,
                            )}
                            method="put"
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="name">Nombre *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={level.name}
                                            className="max-w-sm"
                                            autoFocus
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-destructive">
                                                {errors.name}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="description">
                                            Descripción
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            defaultValue={
                                                level.description ?? ''
                                            }
                                            rows={3}
                                        />
                                        {errors.description && (
                                            <p className="text-sm text-destructive">
                                                {errors.description}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex gap-4">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="order">
                                                Orden *
                                            </Label>
                                            <Input
                                                id="order"
                                                name="order"
                                                type="number"
                                                min={1}
                                                defaultValue={level.order}
                                                className="w-28 font-mono"
                                            />
                                            {errors.order && (
                                                <p className="text-sm text-destructive">
                                                    {errors.order}
                                                </p>
                                            )}
                                        </div>

                                        <div className="grid gap-1.5">
                                            <Label htmlFor="grade_value">
                                                Valor (0 a 5)
                                            </Label>
                                            <Input
                                                id="grade_value"
                                                name="grade_value"
                                                type="number"
                                                step="0.01"
                                                min={0}
                                                max={5}
                                                defaultValue={
                                                    level.grade_value ?? ''
                                                }
                                                className="w-32 font-mono"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                Nota con la que se calculan los
                                                promedios
                                            </p>
                                            {errors.grade_value && (
                                                <p className="text-sm text-destructive">
                                                    {errors.grade_value}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="grid gap-1.5">
                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                id="is_below_basic_threshold"
                                                name="is_below_basic_threshold"
                                                value="1"
                                                defaultChecked={
                                                    level.is_below_basic_threshold
                                                }
                                            />
                                            <Label
                                                htmlFor="is_below_basic_threshold"
                                                className="font-normal"
                                            >
                                                Este nivel define el umbral de
                                                bajo rendimiento
                                            </Label>
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            Los estudiantes cuyo promedio quede
                                            por debajo de este valor se reportan
                                            en riesgo académico
                                        </p>
                                        {errors.is_below_basic_threshold && (
                                            <p className="text-sm text-destructive">
                                                {errors.is_below_basic_threshold}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex gap-2 pt-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Guardar cambios
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={PerformanceLevelController.index.url()}
                                            >
                                                Cancelar
                                            </Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
