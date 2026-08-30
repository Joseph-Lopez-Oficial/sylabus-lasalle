import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as CriterionController from '@/actions/App/Http/Controllers/Admin/EvaluationCriterionController';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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

type Props = { types: { id: number; name: string }[] };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Criterios de Evaluación', href: CriterionController.index.url() },
    { title: 'Nuevo criterio', href: CriterionController.create.url() },
];

export default function EvaluationCriteriaCreate({ types }: Props) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Criterio de Evaluación" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title="Nuevo Criterio de Evaluación">
                    <Button variant="outline" asChild>
                        <Link href={CriterionController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>
                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            action={CriterionController.store.url()}
                            method="post"
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="type">
                                            Tipo de resultado *
                                        </Label>
                                        <Select name="microcurricular_learning_outcome_type_id">
                                            <SelectTrigger
                                                id="type"
                                                className="max-w-sm overflow-hidden"
                                            >
                                                <SelectValue
                                                    placeholder="Seleccione un tipo"
                                                    className="truncate"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {types.length === 0 ? (
                                                    <SelectItem
                                                        value="__empty__"
                                                        disabled
                                                    >
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
                                        <p className="text-xs text-muted-foreground">
                                            El criterio solo se aplicará a los
                                            resultados de aprendizaje de este
                                            tipo
                                        </p>
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
                                            defaultValue={1}
                                            className="w-28 font-mono"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Posición en la que aparecerá al
                                            calificar
                                        </p>
                                        {errors.order && (
                                            <p className="text-sm text-destructive">
                                                {errors.order}
                                            </p>
                                        )}
                                    </div>

                                    <input
                                        type="hidden"
                                        name="is_active"
                                        value="1"
                                    />

                                    <div className="flex gap-2 pt-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Crear criterio
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
