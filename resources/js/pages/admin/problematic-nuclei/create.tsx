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
import type { BreadcrumbItem, Program } from '@/types';

type Props = { programs: Pick<Program, 'id' | 'name'>[] };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Núcleos Problemáticos', href: NucleusController.index.url() },
    { title: 'Nuevo núcleo', href: NucleusController.create.url() },
];

export default function ProblematicNucleiCreate({ programs }: Props) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Núcleo Problemático" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title="Nuevo Núcleo Problemático">
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
                            {...NucleusController.store.form()}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="program_id">
                                            Programa *
                                        </Label>
                                        <Select name="program_id">
                                            <SelectTrigger id="program_id">
                                                <SelectValue placeholder="Selecciona un programa" />
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
                                                : 'Crear núcleo'}
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
