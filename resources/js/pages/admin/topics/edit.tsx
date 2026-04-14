import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as TopicController from '@/actions/App/Http/Controllers/Admin/TopicController';
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
import type { AcademicSpace, BreadcrumbItem, Topic } from '@/types';

type Props = {
    topic: Topic;
    academicSpaces: Pick<AcademicSpace, 'id' | 'name'>[];
};

export default function TopicsEdit({ topic, academicSpaces }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Temas', href: TopicController.index.url() },
        { title: topic.name, href: TopicController.edit.url(topic) },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar: ${topic.name}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader title={`Editar: ${topic.name}`}>
                    <Button variant="outline" asChild>
                        <Link href={TopicController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>
                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <Form
                            {...TopicController.update.form(topic)}
                            className="space-y-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="academic_space_id">
                                            Espacio Académico *
                                        </Label>
                                        <Select
                                            name="academic_space_id"
                                            defaultValue={String(
                                                topic.academic_space_id,
                                            )}
                                        >
                                            <SelectTrigger id="academic_space_id">
                                                <SelectValue />
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
                                        <Label htmlFor="name">Nombre *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={topic.name}
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
                                            defaultValue={topic.order}
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
                                            defaultValue={
                                                topic.description ?? ''
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
                                                href={TopicController.index.url()}
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
