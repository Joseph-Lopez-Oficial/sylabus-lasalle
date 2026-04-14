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
import type { BreadcrumbItem, Competency, ProblematicNucleus } from '@/types';

type Props = {
    competency: Competency;
    nuclei: Pick<ProblematicNucleus, 'id' | 'name'>[];
};

export default function CompetenciesEdit({ competency, nuclei }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Competencias', href: CompetencyController.index.url() },
        {
            title: competency.name,
            href: CompetencyController.edit.url(competency),
        },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar: ${competency.name}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title={`Editar: ${competency.name}`}>
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
                            {...CompetencyController.update.form(competency)}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="problematic_nucleus_id">
                                            Núcleo Problemático *
                                        </Label>
                                        <Select
                                            name="problematic_nucleus_id"
                                            defaultValue={String(
                                                competency.problematic_nucleus_id,
                                            )}
                                        >
                                            <SelectTrigger id="problematic_nucleus_id">
                                                <SelectValue />
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
                                            defaultValue={competency.name}
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
                                                competency.description ?? ''
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
