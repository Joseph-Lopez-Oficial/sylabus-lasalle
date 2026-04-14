import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as FacultyController from '@/actions/App/Http/Controllers/Admin/FacultyController';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Facultades', href: FacultyController.index.url() },
    { title: 'Nueva facultad', href: FacultyController.create.url() },
];

export default function FacultiesCreate() {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Facultad" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title="Nueva Facultad">
                    <Button variant="outline" asChild>
                        <Link href={FacultyController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            {...FacultyController.store.form()}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="name">Nombre *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            placeholder="Ej. Facultad de Ingeniería"
                                            autoFocus
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-destructive">
                                                {errors.name}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="code">Código *</Label>
                                        <Input
                                            id="code"
                                            name="code"
                                            placeholder="Ej. ING"
                                            className="max-w-xs"
                                        />
                                        {errors.code && (
                                            <p className="text-sm text-destructive">
                                                {errors.code}
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
                                            placeholder="Descripción opcional de la facultad..."
                                            rows={3}
                                        />
                                        {errors.description && (
                                            <p className="text-sm text-destructive">
                                                {errors.description}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex justify-end gap-3 pt-2">
                                        <Button variant="outline" asChild>
                                            <Link
                                                href={FacultyController.index.url()}
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
                                                : 'Crear facultad'}
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
