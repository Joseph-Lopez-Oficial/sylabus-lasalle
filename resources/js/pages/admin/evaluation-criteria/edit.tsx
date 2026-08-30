import { Form, Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft } from 'lucide-react';
import * as CriterionController from '@/actions/App/Http/Controllers/Admin/EvaluationCriterionController';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem } from '@/types';

type Criterion = {
    id: number;
    name: string;
    description: string | null;
    order: number;
    is_active: boolean;
    microcurricular_learning_outcome_type_id: number;
};

type Props = {
    criterion: Criterion;
    types: { id: number; name: string }[];
    gradesCount: number;
};

export default function EvaluationCriteriaEdit({
    criterion,
    types,
    gradesCount,
}: Props) {
    const inUse = gradesCount > 0;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Criterios de Evaluación',
            href: CriterionController.index.url(),
        },
        { title: criterion.name, href: CriterionController.edit.url(criterion) },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar: ${criterion.name}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title={`Editar: ${criterion.name}`}>
                    <Button variant="outline" asChild>
                        <Link href={CriterionController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>

                {inUse && (
                    <Alert className="max-w-2xl border-amber-300 bg-amber-50 dark:bg-amber-950/20">
                        <AlertTriangle className="h-4 w-4 text-amber-600" />
                        <AlertDescription className="text-amber-800 dark:text-amber-200">
                            {gradesCount} calificación(es) dependen de este
                            criterio, por lo que no puede cambiarse de tipo ni
                            desactivarse. El nombre y la descripción sí pueden
                            ajustarse.
                        </AlertDescription>
                    </Alert>
                )}

                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            action={CriterionController.update.url(criterion)}
                            method="put"
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="type">
                                            Tipo de resultado *
                                        </Label>
                                        <Select
                                            name="microcurricular_learning_outcome_type_id"
                                            defaultValue={String(
                                                criterion.microcurricular_learning_outcome_type_id,
                                            )}
                                            disabled={inUse}
                                        >
                                            <SelectTrigger
                                                id="type"
                                                className="max-w-sm overflow-hidden"
                                            >
                                                <SelectValue className="truncate" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {types.map((t) => (
                                                    <SelectItem
                                                        key={t.id}
                                                        value={String(t.id)}
                                                    >
                                                        <span className="truncate">
                                                            {t.name}
                                                        </span>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {inUse && (
                                            <input
                                                type="hidden"
                                                name="microcurricular_learning_outcome_type_id"
                                                value={
                                                    criterion.microcurricular_learning_outcome_type_id
                                                }
                                            />
                                        )}
                                        {errors.microcurricular_learning_outcome_type_id && (
                                            <p className="text-sm text-destructive">
                                                {
                                                    errors.microcurricular_learning_outcome_type_id
                                                }
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="name">Nombre *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={criterion.name}
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
                                                criterion.description ?? ''
                                            }
                                            rows={3}
                                        />
                                        {errors.description && (
                                            <p className="text-sm text-destructive">
                                                {errors.description}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="order">Orden *</Label>
                                        <Input
                                            id="order"
                                            name="order"
                                            type="number"
                                            min={1}
                                            defaultValue={criterion.order}
                                            className="w-28 font-mono"
                                        />
                                        {errors.order && (
                                            <p className="text-sm text-destructive">
                                                {errors.order}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-1.5">
                                        <div className="flex items-center gap-2">
                                            {/* Neither an unchecked nor a
                                            disabled box submits anything, so
                                            this hidden field carries the value
                                            the form should fall back to. */}
                                            <input
                                                type="hidden"
                                                name="is_active"
                                                value={inUse ? '1' : '0'}
                                            />
                                            <Checkbox
                                                id="is_active"
                                                name="is_active"
                                                value="1"
                                                defaultChecked={
                                                    criterion.is_active
                                                }
                                                disabled={inUse}
                                            />
                                            <Label
                                                htmlFor="is_active"
                                                className="font-normal"
                                            >
                                                Criterio activo
                                            </Label>
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            Un criterio inactivo deja de pedirse
                                            al calificar, pero conserva las notas
                                            ya registradas
                                        </p>
                                        {errors.is_active && (
                                            <p className="text-sm text-destructive">
                                                {errors.is_active}
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
                                                href={CriterionController.index.url()}
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
