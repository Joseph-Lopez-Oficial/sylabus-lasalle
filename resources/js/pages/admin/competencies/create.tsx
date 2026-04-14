import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as CompetencyController from '@/actions/App/Http/Controllers/Admin/CompetencyController';
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
import type { BreadcrumbItem, ProblematicNucleus } from '@/types';

type Props = { nuclei: Pick<ProblematicNucleus, 'id' | 'name'>[] };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competencias', href: CompetencyController.index.url() },
    { title: 'Nueva competencia', href: CompetencyController.create.url() },
];

export default function CompetenciesCreate({ nuclei }: Props) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Competencia" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title="Nueva Competencia">
                    <Button variant="outline" asChild>
                        <Link href={CompetencyController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>
                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            {...CompetencyController.store.form()}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="problematic_nucleus_id">
                                            Núcleo Problemático *
                                        </Label>
                                        <Select name="problematic_nucleus_id">
                                            <SelectTrigger id="problematic_nucleus_id">
                                                <SelectValue placeholder="Selecciona un núcleo" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {nuclei.map((n) => (
                                                    <SelectItem
                                                        key={n.id}
                                                        value={String(n.id)}
                                                    >
                                                        {n.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.problematic_nucleus_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.problematic_nucleus_id}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="name">Nombre *</Label>
                                        <Input
                                            id="name"
                                            name="name"
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
                                                href={CompetencyController.index.url()}
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
                                                : 'Crear competencia'}
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
