import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as OutcomeController from '@/actions/App/Http/Controllers/Admin/MicrocurricularLearningOutcomeController';
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
import type {
    AcademicSpace,
    BreadcrumbItem,
    MesocurricularLearningOutcome,
    MicrocurricularLearningOutcomeType,
} from '@/types';

type Props = {
    academicSpaces: Pick<AcademicSpace, 'id' | 'code' | 'name'>[];
    types: Pick<MicrocurricularLearningOutcomeType, 'id' | 'name'>[];
    mesocurricularOutcomes: Pick<
        MesocurricularLearningOutcome,
        'id' | 'code' | 'description'
    >[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Resultados Microcurriculares',
        href: OutcomeController.index.url(),
    },
    { title: 'Nuevo resultado', href: OutcomeController.create.url() },
];

export default function MicrocurricularOutcomesCreate({
    academicSpaces,
    types,
    mesocurricularOutcomes,
}: Props) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Resultado Microcurricular" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title="Nuevo Resultado Microcurricular">
                    <Button variant="outline" asChild>
                        <Link href={OutcomeController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>
                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            action={OutcomeController.store.url()}
                            method="post"
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="academic_space_id">
                                            Espacio Académico *
                                        </Label>
                                        <Select name="academic_space_id">
                                            <SelectTrigger id="academic_space_id" className="overflow-hidden">
                                                <SelectValue placeholder="Selecciona un espacio" className="truncate" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {academicSpaces.map((s) => (
                                                    <SelectItem
                                                        key={s.id}
                                                        value={String(s.id)}
                                                    >
                                                        <span className="flex min-w-0 gap-1.5">
                                                            <span className="shrink-0 font-mono text-xs text-muted-foreground">{s.code}</span>
                                                            <span className="truncate">{s.name}</span>
                                                        </span>
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
                                        <Label htmlFor="code">Código *</Label>
                                        <Input
                                            id="code"
                                            name="code"
                                            placeholder="Ej. RA1"
                                            className="max-w-xs font-mono"
                                        />
                                        {errors.code && (
                                            <p className="text-sm text-destructive">
                                                {errors.code}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="type_id">
                                            Tipo de resultado *
                                        </Label>
                                        <Select name="type_id">
                                            <SelectTrigger id="type_id">
                                                <SelectValue placeholder="Selecciona el tipo" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {types.map((t) => (
                                                    <SelectItem
                                                        key={t.id}
                                                        value={String(t.id)}
                                                    >
                                                        {t.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.type_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.type_id}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="mesocurricular_learning_outcome_id">
                                            Resultado Mesocurricular vinculado
                                            <span className="ml-1 text-xs text-muted-foreground">
                                                (opcional)
                                            </span>
                                        </Label>
                                        <Select name="mesocurricular_learning_outcome_id">
                                            <SelectTrigger id="mesocurricular_learning_outcome_id" className="overflow-hidden">
                                                <SelectValue placeholder="Ninguno" className="truncate" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {mesocurricularOutcomes.map(
                                                    (m) => (
                                                        <SelectItem
                                                            key={m.id}
                                                            value={String(m.id)}
                                                        >
                                                            <span className="flex min-w-0 gap-1.5">
                                                                <span className="shrink-0 font-mono text-xs text-muted-foreground">{m.code}</span>
                                                                <span className="truncate">{m.description}</span>
                                                            </span>
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        {errors.mesocurricular_learning_outcome_id && (
                                            <p className="text-sm text-destructive">
                                                {
                                                    errors.mesocurricular_learning_outcome_id
                                                }
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="description">
                                            Descripción *
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            rows={5}
                                            autoFocus
                                            placeholder="Describe el resultado de aprendizaje microcurricular..."
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
                                                href={OutcomeController.index.url()}
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
                                                : 'Crear resultado'}
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
