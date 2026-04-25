import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as PeriodController from '@/actions/App/Http/Controllers/Admin/AcademicPeriodController';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Períodos Académicos', href: PeriodController.index.url() },
    { title: 'Nuevo período', href: PeriodController.create.url() },
];

export default function AcademicPeriodsCreate() {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Período Académico" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title="Nuevo Período Académico">
                    <Button variant="outline" asChild>
                        <Link href={PeriodController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>
                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            action={PeriodController.store.url()}
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
                                            placeholder="Ej. 2024-1"
                                            className="max-w-xs font-mono"
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
                                            rows={2}
                                            placeholder="Ej. Primer semestre de 2024"
                                        />
                                        {errors.description && (
                                            <p className="text-sm text-destructive">
                                                {errors.description}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="start_date">
                                                Fecha de inicio
                                            </Label>
                                            <Input
                                                id="start_date"
                                                name="start_date"
                                                type="date"
                                            />
                                            {errors.start_date && (
                                                <p className="text-sm text-destructive">
                                                    {errors.start_date}
                                                </p>
                                            )}
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="end_date">
                                                Fecha de fin
                                            </Label>
                                            <Input
                                                id="end_date"
                                                name="end_date"
                                                type="date"
                                            />
                                            {errors.end_date && (
                                                <p className="text-sm text-destructive">
                                                    {errors.end_date}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex justify-end gap-3 pt-2">
                                        <Button variant="outline" asChild>
                                            <Link
                                                href={PeriodController.index.url()}
                                            >
                                                Cancelar
                                            </Link>
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? 'Guardando...'
                                                : 'Crear período'}
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
