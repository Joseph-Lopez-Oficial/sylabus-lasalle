import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as SpaceController from '@/actions/App/Http/Controllers/Admin/AcademicSpaceController';
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
import type { AcademicSpace, BreadcrumbItem, Competency } from '@/types';

type Props = {
    academicSpace: AcademicSpace;
    competencies: Pick<Competency, 'id' | 'name'>[];
};

export default function AcademicSpacesEdit({
    academicSpace,
    competencies,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Espacios Académicos', href: SpaceController.index.url() },
        {
            title: academicSpace.name,
            href: SpaceController.show.url(academicSpace),
        },
        { title: 'Editar', href: SpaceController.edit.url(academicSpace) },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar: ${academicSpace.name}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title={`Editar: ${academicSpace.name}`}>
                    <Button variant="outline" asChild>
                        <Link href={SpaceController.show.url(academicSpace)}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>
                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            {...SpaceController.update.form(academicSpace)}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="competency_id">
                                            Competencia *
                                        </Label>
                                        <Select
                                            name="competency_id"
                                            defaultValue={String(
                                                academicSpace.competency_id,
                                            )}
                                        >
                                            <SelectTrigger id="competency_id">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {competencies.map((c) => (
                                                    <SelectItem
                                                        key={c.id}
                                                        value={String(c.id)}
                                                    >
                                                        {c.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.competency_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.competency_id}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="name">Nombre *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={academicSpace.name}
                                            autoFocus
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-destructive">
                                                {errors.name}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="code">
                                                Código *
                                            </Label>
                                            <Input
                                                id="code"
                                                name="code"
                                                defaultValue={
                                                    academicSpace.code
                                                }
                                            />
                                            {errors.code && (
                                                <p className="text-sm text-destructive">
                                                    {errors.code}
                                                </p>
                                            )}
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="credits">
                                                Créditos *
                                            </Label>
                                            <Input
                                                id="credits"
                                                name="credits"
                                                type="number"
                                                min={1}
                                                defaultValue={
                                                    academicSpace.credits
                                                }
                                            />
                                            {errors.credits && (
                                                <p className="text-sm text-destructive">
                                                    {errors.credits}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="semester">
                                            Semestre
                                        </Label>
                                        <Input
                                            id="semester"
                                            name="semester"
                                            type="number"
                                            min={1}
                                            defaultValue={
                                                academicSpace.semester ?? ''
                                            }
                                            className="max-w-xs"
                                        />
                                        {errors.semester && (
                                            <p className="text-sm text-destructive">
                                                {errors.semester}
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
                                                academicSpace.description ?? ''
                                            }
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
                                                href={SpaceController.show.url(
                                                    academicSpace,
                                                )}
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
