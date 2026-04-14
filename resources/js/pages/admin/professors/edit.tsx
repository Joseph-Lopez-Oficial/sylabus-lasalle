import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as ProfessorController from '@/actions/App/Http/Controllers/Admin/ProfessorController';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem, Professor } from '@/types';

type Props = { professor: Professor };

export default function ProfessorsEdit({ professor }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Profesores', href: ProfessorController.index.url() },
        {
            title: `${professor.first_name} ${professor.last_name}`,
            href: ProfessorController.edit.url(professor),
        },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Editar: ${professor.first_name} ${professor.last_name}`}
            />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title={`Editar: ${professor.first_name} ${professor.last_name}`}
                >
                    <div className="flex items-center gap-2">
                        {professor.user_id ? (
                            <Badge
                                variant="outline"
                                className="border-green-300 text-green-700"
                            >
                                Con cuenta de acceso
                            </Badge>
                        ) : (
                            <Badge
                                variant="outline"
                                className="text-muted-foreground"
                            >
                                Sin cuenta de acceso
                            </Badge>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={ProfessorController.index.url()}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                    </div>
                </PageHeader>

                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            {...ProfessorController.update.form(professor)}
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
                                                    professor.first_name
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
                                                defaultValue={
                                                    professor.last_name
                                                }
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
                                                professor.document_number
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
                                        <Label htmlFor="institutional_email">
                                            Correo institucional *
                                        </Label>
                                        <Input
                                            id="institutional_email"
                                            name="institutional_email"
                                            type="email"
                                            defaultValue={
                                                professor.institutional_email
                                            }
                                        />
                                        {errors.institutional_email && (
                                            <p className="text-sm text-destructive">
                                                {errors.institutional_email}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="phone">Teléfono</Label>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            type="tel"
                                            defaultValue={professor.phone ?? ''}
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
                                                href={ProfessorController.index.url()}
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
