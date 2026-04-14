import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
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
import type { Activity, BreadcrumbItem } from '@/types';

type Props = { activities: Pick<Activity, 'id' | 'name'>[] };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Productos', href: ProductController.index.url() },
    { title: 'Nuevo producto', href: ProductController.create.url() },
];

export default function ProductsCreate({ activities }: Props) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Producto" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title="Nuevo Producto">
                    <Button variant="outline" asChild>
                        <Link href={ProductController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>
                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            {...ProductController.store.form()}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="activity_id">
                                            Actividad *
                                        </Label>
                                        <Select name="activity_id">
                                            <SelectTrigger id="activity_id">
                                                <SelectValue placeholder="Selecciona una actividad" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {activities.map((a) => (
                                                    <SelectItem
                                                        key={a.id}
                                                        value={String(a.id)}
                                                    >
                                                        {a.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.activity_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.activity_id}
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
                                        <Label htmlFor="order">Orden *</Label>
                                        <Input
                                            id="order"
                                            name="order"
                                            type="number"
                                            min={1}
                                            defaultValue={1}
                                            className="max-w-xs"
                                        />
                                        {errors.order && (
                                            <p className="text-sm text-destructive">
                                                {errors.order}
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
                                                href={ProductController.index.url()}
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
                                                : 'Crear producto'}
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
