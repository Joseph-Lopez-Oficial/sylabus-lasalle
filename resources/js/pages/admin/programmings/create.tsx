import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as ProgrammingController from '@/actions/App/Http/Controllers/Admin/ProgrammingController';
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
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    AcademicSpace,
    BreadcrumbItem,
    Modality,
    Professor,
} from '@/types';

type Props = {
    academicSpaces: Pick<AcademicSpace, 'id' | 'name'>[];
    professors: Pick<Professor, 'id' | 'first_name' | 'last_name'>[];
    modalities: Pick<Modality, 'id' | 'name'>[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Programaciones', href: ProgrammingController.index.url() },
    { title: 'Nueva programación', href: ProgrammingController.create.url() },
];

export default function ProgrammingsCreate({
    academicSpaces,
    professors,
    modalities,
}: Props) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Programación" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title="Nueva Programación">
                    <Button variant="outline" asChild>
                        <Link href={ProgrammingController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>
                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            {...ProgrammingController.store.form()}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="academic_space_id">
                                            Espacio Académico *
                                        </Label>
                                        <Select name="academic_space_id">
                                            <SelectTrigger id="academic_space_id">
                                                <SelectValue placeholder="Selecciona un espacio académico" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {academicSpaces.map((s) => (
                                                    <SelectItem
                                                        key={s.id}
                                                        value={String(s.id)}
                                                    >
                                                        {s.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.academic_space_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.academic_space_id}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="professor_id">
                                            Profesor *
                                        </Label>
                                        <Select name="professor_id">
                                            <SelectTrigger id="professor_id">
                                                <SelectValue placeholder="Selecciona un profesor" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {professors.map((p) => (
                                                    <SelectItem
                                                        key={p.id}
                                                        value={String(p.id)}
                                                    >
                                                        {p.first_name}{' '}
                                                        {p.last_name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.professor_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.professor_id}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="modality_id">
                                            Modalidad *
                                        </Label>
                                        <Select name="modality_id">
                                            <SelectTrigger id="modality_id">
                                                <SelectValue placeholder="Selecciona la modalidad" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {modalities.map((m) => (
                                                    <SelectItem
                                                        key={m.id}
                                                        value={String(m.id)}
                                                    >
                                                        {m.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.modality_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.modality_id}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="period">
                                                Período académico *
                                            </Label>
                                            <Input
                                                id="period"
                                                name="period"
                                                placeholder="Ej. 2024-1"
                                                autoFocus
                                            />
                                            {errors.period && (
                                                <p className="text-sm text-destructive">
                                                    {errors.period}
                                                </p>
                                            )}
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="group">Grupo</Label>
                                            <Input
                                                id="group"
                                                name="group"
                                                placeholder="Ej. A"
                                            />
                                            {errors.group && (
                                                <p className="text-sm text-destructive">
                                                    {errors.group}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex justify-end gap-3 pt-2">
                                        <Button variant="outline" asChild>
                                            <Link
                                                href={ProgrammingController.index.url()}
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
                                                : 'Crear programación'}
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
