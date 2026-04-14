import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as NucleusController from '@/actions/App/Http/Controllers/Admin/ProblematicNucleusController';
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
import type { BreadcrumbItem, Program, ProblematicNucleus } from '@/types';

type Props = {
    nucleus: ProblematicNucleus;
    programs: Pick<Program, 'id' | 'name'>[];
};

export default function ProblematicNucleiEdit({ nucleus, programs }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Núcleos Problemáticos', href: NucleusController.index.url() },
        { title: nucleus.name, href: NucleusController.edit.url(nucleus) },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar: ${nucleus.name}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title={`Editar: ${nucleus.name}`}>
                    <Button variant="outline" asChild>
                        <Link href={NucleusController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>
                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            {...NucleusController.update.form(nucleus)}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="program_id">
                                            Programa *
                                        </Label>
                                        <Select
                                            name="program_id"
                                            defaultValue={String(
                                                nucleus.program_id,
                                            )}
                                        >
                                            <SelectTrigger id="program_id">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {programs.map((p) => (
                                                    <SelectItem
                                                        key={p.id}
                                                        value={String(p.id)}
                                                    >
                                                        {p.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.program_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.program_id}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="name">Nombre *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={nucleus.name}
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
                                                nucleus.description ?? ''
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
                                                href={NucleusController.index.url()}
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
