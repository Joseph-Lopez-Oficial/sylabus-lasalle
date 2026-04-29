import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as PeriodController from '@/actions/App/Http/Controllers/Admin/AcademicPeriodController';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { AcademicPeriod, BreadcrumbItem } from '@/types';

type Props = { period: AcademicPeriod };

export default function AcademicPeriodsEdit({ period }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Períodos Académicos', href: PeriodController.index.url() },
        { title: period.name, href: PeriodController.edit.url(period) },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar: ${period.name}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title={`Editar: ${period.name}`}>
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
                            action={PeriodController.update.url(period)}
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
                                            defaultValue={period.name}
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
                                            defaultValue={
                                                period.description ?? ''
                                            }
                                            rows={2}
                                        />
                                        {errors.description && (
                                            <p className="text-sm text-destructive">
                                                {errors.description}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-1.5">
                                            <Label>Fecha de inicio</Label>
                                            <DatePicker
                                                name="start_date"
                                                defaultValue={period.start_date}
                                                placeholder="Fecha de inicio"
                                            />
                                            {errors.start_date && (
                                                <p className="text-sm text-destructive">
                                                    {errors.start_date}
                                                </p>
                                            )}
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label>Fecha de fin</Label>
                                            <DatePicker
                                                name="end_date"
                                                defaultValue={period.end_date}
                                                placeholder="Fecha de fin"
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
                                                : 'Guardar cambios'}
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
