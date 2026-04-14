import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as ProgramController from '@/actions/App/Http/Controllers/Admin/ProgramController';
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
import type { BreadcrumbItem, Faculty } from '@/types';

type Props = { faculties: Pick<Faculty, 'id' | 'name'>[] };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Programas', href: ProgramController.index.url() },
    { title: 'Nuevo programa', href: ProgramController.create.url() },
];

export default function ProgramsCreate({ faculties }: Props) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Programa" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title="Nuevo Programa">
                    <Button variant="outline" asChild>
                        <Link href={ProgramController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>

                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            {...ProgramController.store.form()}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="faculty_id">
                                            Facultad *
                                        </Label>
                                        <Select name="faculty_id">
                                            <SelectTrigger id="faculty_id">
                                                <SelectValue placeholder="Selecciona una facultad" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {faculties.map((f) => (
                                                    <SelectItem
                                                        key={f.id}
                                                        value={String(f.id)}
                                                    >
                                                        {f.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.faculty_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.faculty_id}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="name">Nombre *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            placeholder="Ej. Ingeniería de Sistemas"
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
                                            placeholder="Ej. ISC"
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
                                                href={ProgramController.index.url()}
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
                                                : 'Crear programa'}
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
