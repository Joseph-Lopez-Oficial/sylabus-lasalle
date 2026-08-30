import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as PerformanceLevelController from '@/actions/App/Http/Controllers/Admin/PerformanceLevelController';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Niveles de Desempeño',
        href: PerformanceLevelController.index.url(),
    },
    { title: 'Nuevo nivel', href: PerformanceLevelController.create.url() },
];

export default function PerformanceLevelsCreate() {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Nivel de Desempeño" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title="Nuevo Nivel de Desempeño">
                    <Button variant="outline" asChild>
                        <Link href={PerformanceLevelController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            action={PerformanceLevelController.store.url()}
                            method="post"
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
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
                                                defaultValue={1}
                                                className="w-28 font-mono"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                1 es el nivel más bajo
                                            </p>
                                            {errors.order && (
                                                <p className="text-sm text-destructive">
                                                    {errors.order}
                                                </p>
                                            )}
                                        </div>

                                        <div className="grid gap-1.5">
                                            <Label htmlFor="grade_value">
                                                Valor
                                            </Label>
                                            <Input
                                                id="grade_value"
                                                name="grade_value"
                                                type="number"
                                                step="0.01"
                                                min={0}
                                                max={5}
                                                className="w-28 font-mono"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                Sin valor no promedia
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
                                            <input
                                                type="hidden"
                                                name="is_below_basic_threshold"
                                                value="0"
                                            />
                                            <Checkbox
                                                id="is_below_basic_threshold"
                                                name="is_below_basic_threshold"
                                                value="1"
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
                                            Marcarlo quita la marca al nivel que
                                            la tenga actualmente
                                        </p>
                                        {errors.is_below_basic_threshold && (
                                            <p className="text-sm text-destructive">
                                                {errors.is_below_basic_threshold}
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
                                            Crear nivel
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
