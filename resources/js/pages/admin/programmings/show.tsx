import { Form, Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Power, Upload, Users } from 'lucide-react';
import { useRef, useState } from 'react';
import * as EnrollmentController from '@/actions/App/Http/Controllers/Admin/EnrollmentController';
import * as ProgrammingController from '@/actions/App/Http/Controllers/Admin/ProgrammingController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem, Enrollment, Programming, Student } from '@/types';

type ImportResult = { row: number; status: string; message: string };

type ProgrammingWithRelations = Programming & {
    academic_space: { id: number; name: string; code: string };
    professor: { id: number; first_name: string; last_name: string };
    modality: { id: number; name: string };
    enrollments: (Enrollment & { student: Student })[];
};

type Props = {
    programming: ProgrammingWithRelations;
    students: Pick<
        Student,
        'id' | 'first_name' | 'last_name' | 'document_number'
    >[];
    import_results?: ImportResult[];
};

export default function ProgrammingsShow({
    programming,
    students,
    import_results,
}: Props) {
    const [selectedStudentId, setSelectedStudentId] = useState('');
    const [enrolling, setEnrolling] = useState(false);
    const [toggleTarget, setToggleTarget] = useState<Enrollment | null>(null);
    const [toggling, setToggling] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Programaciones', href: ProgrammingController.index.url() },
        {
            title: `${programming.period}${programming.group ? ' · ' + programming.group : ''}`,
            href: ProgrammingController.show.url(programming),
        },
    ];

    function handleEnroll() {
        if (!selectedStudentId) return;
        setEnrolling(true);
        router.post(
            EnrollmentController.store.url(programming),
            { student_id: selectedStudentId },
            {
                preserveScroll: true,
                onFinish: () => {
                    setEnrolling(false);
                    setSelectedStudentId('');
                },
            },
        );
    }

    function handleToggleEnrollment() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            EnrollmentController.toggleStatus.url({
                programming: programming.id,
                enrollment: toggleTarget.id,
            }),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setToggling(false);
                    setToggleTarget(null);
                },
            },
        );
    }

    const enrolledIds = new Set(
        programming.enrollments.map((e) => e.student_id),
    );
    const availableStudents = students.filter((s) => !enrolledIds.has(s.id));

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Programación: ${programming.period}`} />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title={`${programming.academic_space.name}`}
                    description={`${programming.period}${programming.group ? ' · Grupo ' + programming.group : ''} · ${programming.modality.name}`}
                >
                    <StatusBadge isActive={programming.is_active} />
                    <Button variant="outline" asChild>
                        <Link
                            href={ProgrammingController.edit.url(programming)}
                        >
                            <Pencil className="mr-2 h-4 w-4" />
                            Editar
                        </Link>
                    </Button>
                </PageHeader>

                <Tabs defaultValue="info">
                    <TabsList>
                        <TabsTrigger value="info">
                            Información general
                        </TabsTrigger>
                        <TabsTrigger value="enrollments">
                            Inscripciones
                            <Badge variant="secondary" className="ml-2">
                                {programming.enrollments.length}
                            </Badge>
                        </TabsTrigger>
                    </TabsList>

                    {/* Tab: Info */}
                    <TabsContent value="info" className="mt-4">
                        <Card className="max-w-2xl">
                            <CardContent className="pt-6">
                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p className="text-muted-foreground">
                                            Espacio Académico
                                        </p>
                                        <p className="font-medium">
                                            {programming.academic_space.name} (
                                            {programming.academic_space.code})
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">
                                            Profesor
                                        </p>
                                        <p className="font-medium">
                                            {programming.professor.first_name}{' '}
                                            {programming.professor.last_name}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">
                                            Período
                                        </p>
                                        <p className="font-medium">
                                            {programming.period}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">
                                            Grupo
                                        </p>
                                        <p className="font-medium">
                                            {programming.group ?? 'Único'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">
                                            Modalidad
                                        </p>
                                        <p className="font-medium">
                                            {programming.modality.name}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">
                                            Estudiantes inscritos
                                        </p>
                                        <p className="font-medium">
                                            {programming.enrollments.length}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Tab: Inscripciones */}
                    <TabsContent value="enrollments" className="mt-4 space-y-4">
                        {/* Agregar estudiante individualmente */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm">
                                    Inscribir estudiante
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex gap-2">
                                    <Select
                                        value={selectedStudentId}
                                        onValueChange={setSelectedStudentId}
                                    >
                                        <SelectTrigger className="flex-1">
                                            <SelectValue placeholder="Busca por nombre o código..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableStudents.length === 0 ? (
                                                <SelectItem
                                                    value="_none"
                                                    disabled
                                                >
                                                    Todos los estudiantes ya
                                                    están inscritos
                                                </SelectItem>
                                            ) : (
                                                availableStudents.map((s) => (
                                                    <SelectItem
                                                        key={s.id}
                                                        value={String(s.id)}
                                                    >
                                                        {s.first_name}{' '}
                                                        {s.last_name} —{' '}
                                                        {s.document_number}
                                                    </SelectItem>
                                                ))
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <Button
                                        onClick={handleEnroll}
                                        disabled={
                                            !selectedStudentId || enrolling
                                        }
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        {enrolling
                                            ? 'Inscribiendo...'
                                            : 'Inscribir'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Importación masiva */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm">
                                    Importación masiva por Excel
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <p className="text-sm text-muted-foreground">
                                    Sube un archivo Excel con los documentos de
                                    los estudiantes a inscribir. El archivo debe
                                    tener una columna llamada{' '}
                                    <code className="rounded bg-muted px-1 py-0.5 font-mono text-xs">
                                        documento
                                    </code>
                                    .
                                </p>
                                <Form
                                    action={EnrollmentController.importMethod.url(
                                        programming,
                                    )}
                                    method="post"
                                    encType="multipart/form-data"
                                    className="flex gap-2"
                                >
                                    {({ processing }) => (
                                        <>
                                            <Input
                                                ref={fileInputRef}
                                                name="file"
                                                type="file"
                                                accept=".xlsx,.xls,.csv"
                                                className="flex-1"
                                            />
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                disabled={processing}
                                            >
                                                <Upload className="mr-2 h-4 w-4" />
                                                {processing
                                                    ? 'Procesando...'
                                                    : 'Importar'}
                                            </Button>
                                        </>
                                    )}
                                </Form>

                                {import_results &&
                                    import_results.length > 0 && (
                                        <div className="mt-3 max-h-48 space-y-1 overflow-y-auto rounded-md border p-3">
                                            {import_results.map((r) => (
                                                <p
                                                    key={r.row}
                                                    className={`text-xs ${r.status === 'error' ? 'text-destructive' : 'text-muted-foreground'}`}
                                                >
                                                    Fila {r.row}: {r.message}
                                                </p>
                                            ))}
                                        </div>
                                    )}
                            </CardContent>
                        </Card>

                        {/* Lista de inscritos */}
                        {programming.enrollments.length === 0 ? (
                            <EmptyState
                                title="Sin estudiantes inscritos"
                                description="Inscribe estudiantes individualmente o mediante importación masiva."
                                icon={Users}
                            />
                        ) : (
                            <div className="rounded-md border">
                                <table className="w-full text-sm">
                                    <thead className="border-b bg-muted/50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-medium">
                                                Estudiante
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium">
                                                Documento
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium">
                                                Estado
                                            </th>
                                            <th className="px-4 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {programming.enrollments.map(
                                            (enrollment) => (
                                                <tr
                                                    key={enrollment.id}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="px-4 py-3">
                                                        {
                                                            enrollment.student
                                                                ?.first_name
                                                        }{' '}
                                                        {
                                                            enrollment.student
                                                                ?.last_name
                                                        }
                                                    </td>
                                                    <td className="px-4 py-3 font-mono text-xs">
                                                        {
                                                            enrollment.student
                                                                ?.document_number
                                                        }
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <StatusBadge
                                                            isActive={
                                                                enrollment.is_active
                                                            }
                                                            activeLabel="Inscrito"
                                                            inactiveLabel="Retirado"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                setToggleTarget(
                                                                    enrollment,
                                                                )
                                                            }
                                                        >
                                                            <Power className="h-4 w-4" />
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </TabsContent>
                </Tabs>
            </div>

            <ConfirmDialog
                open={!!toggleTarget}
                onOpenChange={(open) => {
                    if (!open) setToggleTarget(null);
                }}
                title={
                    toggleTarget?.is_active
                        ? 'Retirar estudiante'
                        : 'Reinstalar inscripción'
                }
                description={`¿Confirmas ${toggleTarget?.is_active ? 'retirar' : 'reinstalar'} la inscripción de este estudiante?`}
                confirmLabel={
                    toggleTarget?.is_active ? 'Retirar' : 'Reinstalar'
                }
                variant={toggleTarget?.is_active ? 'destructive' : 'default'}
                loading={toggling}
                onConfirm={handleToggleEnrollment}
            />
        </AdminLayout>
    );
}
