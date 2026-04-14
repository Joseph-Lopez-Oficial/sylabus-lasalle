import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as OutcomeController from '@/actions/App/Http/Controllers/Admin/MesocurricularLearningOutcomeController';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import type { BreadcrumbItem, Competency } from '@/types';

type Props = { competencies: Pick<Competency, 'id' | 'name'>[] };

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Resultados Mesocurriculares',
        href: OutcomeController.index.url(),
    },
    { title: 'Nuevo resultado', href: OutcomeController.create.url() },
];

export default function MesocurricularOutcomesCreate({ competencies }: Props) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Resultado Mesocurricular" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title="Nuevo Resultado Mesocurricular">
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
                            {...OutcomeController.store.form()}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="competency_id">
                                            Competencia *
                                        </Label>
                                        <Select name="competency_id">
                                            <SelectTrigger id="competency_id">
                                                <SelectValue placeholder="Selecciona una competencia" />
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
                                        <Label htmlFor="description">
                                            Descripción del resultado *
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            rows={5}
                                            autoFocus
                                            placeholder="Describe el resultado de aprendizaje mesocurricular..."
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
