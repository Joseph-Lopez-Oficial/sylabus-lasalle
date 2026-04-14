import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as StudentController from '@/actions/App/Http/Controllers/Admin/StudentController';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem, Student } from '@/types';

type Props = { student: Student };

export default function StudentsEdit({ student }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Estudiantes', href: StudentController.index.url() },
        {
            title: `${student.first_name} ${student.last_name}`,
            href: StudentController.edit.url(student),
        },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Editar: ${student.first_name} ${student.last_name}`}
            />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title={`Editar: ${student.first_name} ${student.last_name}`}
                >
                    <Button variant="outline" asChild>
                        <Link href={StudentController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>
                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            {...StudentController.update.form(student)}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="first_name">
                                                Nombres *
                                            </Label>
                                            <Input
                                                id="first_name"
                                                name="first_name"
                                                defaultValue={
                                                    student.first_name
                                                }
                                                autoFocus
                                            />
                                            {errors.first_name && (
                                                <p className="text-sm text-destructive">
                                                    {errors.first_name}
                                                </p>
                                            )}
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="last_name">
                                                Apellidos *
                                            </Label>
                                            <Input
                                                id="last_name"
                                                name="last_name"
                                                defaultValue={student.last_name}
                                            />
                                            {errors.last_name && (
                                                <p className="text-sm text-destructive">
                                                    {errors.last_name}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="document_number">
                                            Número de documento *
                                        </Label>
                                        <Input
                                            id="document_number"
                                            name="document_number"
                                            defaultValue={
                                                student.document_number
                                            }
                                            className="max-w-xs"
                                        />
                                        {errors.document_number && (
                                            <p className="text-sm text-destructive">
                                                {errors.document_number}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="email">
                                            Correo electrónico *
                                        </Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={student.email}
                                        />
                                        {errors.email && (
                                            <p className="text-sm text-destructive">
                                                {errors.email}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="phone">Teléfono</Label>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            type="tel"
                                            defaultValue={student.phone ?? ''}
                                            className="max-w-xs"
                                        />
                                        {errors.phone && (
                                            <p className="text-sm text-destructive">
                                                {errors.phone}
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex justify-end gap-3 pt-2">
                                        <Button variant="outline" asChild>
                                            <Link
                                                href={StudentController.index.url()}
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
