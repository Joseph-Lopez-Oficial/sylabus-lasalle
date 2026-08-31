import { Form, Head, Link, router } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronUp,
    FileSpreadsheet,
    FileText,
    Pencil,
    Plus,
    Power,
    Upload,
    Users,
    X,
} from 'lucide-react';
import { BarChart2 } from 'lucide-react';
import { useRef, useState } from 'react';
import * as AdminAnalysisController from '@/actions/App/Http/Controllers/Admin/AcademicSpaceAnalysisController';
import * as EnrollmentController from '@/actions/App/Http/Controllers/Admin/EnrollmentController';
import * as ProgrammingController from '@/actions/App/Http/Controllers/Admin/ProgrammingController';
import {
    ClientPagination,
    useClientPagination,
} from '@/components/client-pagination';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DownloadButton } from '@/components/download-button';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
    // Paged in the browser: the whole programming arrives with the screen, so
    // there is nothing to ask the server for.
    const enrolledPage = useClientPagination(programming.enrollments);

    const [search, setSearch] = useState('');
    const [selectedStudentId, setSelectedStudentId] = useState('');
    const [enrolling, setEnrolling] = useState(false);
    const [toggleTarget, setToggleTarget] = useState<Enrollment | null>(null);
    const [toggling, setToggling] = useState(false);
    const [enrollSectionOpen, setEnrollSectionOpen] = useState(false);
    const [dragOver, setDragOver] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Programaciones', href: ProgrammingController.index.url() },
        {
            title: `${programming.academic_period?.name ?? ''}${programming.group ? ' · ' + programming.group : ''}`,
            href: ProgrammingController.show.url(programming),
        },
    ];

    const enrolledIds = new Set(
        programming.enrollments.map((e) => e.student_id),
    );
    const availableStudents = students.filter((s) => !enrolledIds.has(s.id));
    const filteredStudents = search.trim()
        ? availableStudents.filter((s) => {
              const q = search.toLowerCase();
              return (
                  `${s.first_name} ${s.last_name}`.toLowerCase().includes(q) ||
                  s.document_number.includes(q)
              );
          })
        : availableStudents;
    const selectedStudent = availableStudents.find(
        (s) => String(s.id) === selectedStudentId,
    );

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
                    setSearch('');
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

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Programación: ${programming.academic_period?.name ?? ''}`}
            />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title={`${programming.academic_space.name}`}
                    description={`${programming.academic_period?.name ?? ''}${programming.group ? ' · Grupo ' + programming.group : ''} · ${programming.modality.name}`}
                >
                    <StatusBadge isActive={programming.is_active} />
                    <DownloadButton
                        href={ProgrammingController.downloadInstitutionalReport.url(
                            programming,
                        )}
                        icon={<FileSpreadsheet className="mr-2 h-4 w-4" />}
                    >
                        Reporte institucional
                    </DownloadButton>
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
                        <TabsTrigger value="stats" asChild>
                            <Link
                                href={ProgrammingController.statistics.url(
                                    programming,
                                )}
                                className="flex items-center gap-1.5"
                            >
                                <BarChart2 className="h-3.5 w-3.5" />
                                Estadísticas
                            </Link>
                        </TabsTrigger>
                        <TabsTrigger value="analysis" asChild>
                            <Link
                                href={AdminAnalysisController.show.url(
                                    programming,
                                )}
                                className="flex items-center gap-1.5"
                            >
                                <FileText className="h-3.5 w-3.5" />
                                Análisis
                            </Link>
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
                                            {programming.academic_period
                                                ?.name ?? ''}
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
                        {/* Inscripción individual + masiva */}
                        <Card>
                            <CardHeader
                                className="cursor-pointer pb-3 select-none"
                                onClick={() => setEnrollSectionOpen((v) => !v)}
                            >
                                <CardTitle className="flex items-center justify-between text-sm">
                                    <span>Inscribir estudiantes</span>
                                    {enrollSectionOpen ? (
                                        <ChevronUp className="h-4 w-4 text-muted-foreground" />
                                    ) : (
                                        <ChevronDown className="h-4 w-4 text-muted-foreground" />
                                    )}
                                </CardTitle>
                            </CardHeader>
                            {enrollSectionOpen && (
                                <CardContent className="space-y-4">
                                    {/* Combobox individual */}
                                    <div className="space-y-1.5">
                                        <p className="text-xs font-medium text-muted-foreground">
                                            Inscripción individual
                                        </p>
                                        <div className="flex gap-2">
                                            <div className="relative flex-1">
                                                <Input
                                                    placeholder="Busca por nombre o documento..."
                                                    value={
                                                        selectedStudent
                                                            ? `${selectedStudent.first_name} ${selectedStudent.last_name} — ${selectedStudent.document_number}`
                                                            : search
                                                    }
                                                    onChange={(e) => {
                                                        setSearch(
                                                            e.target.value,
                                                        );
                                                        setSelectedStudentId(
                                                            '',
                                                        );
                                                    }}
                                                />
                                                {search &&
                                                    !selectedStudentId && (
                                                        <div className="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-md border bg-popover shadow-md">
                                                            {filteredStudents.length ===
                                                            0 ? (
                                                                <p className="px-3 py-2 text-sm text-muted-foreground">
                                                                    Sin
                                                                    resultados
                                                                </p>
                                                            ) : (
                                                                filteredStudents.map(
                                                                    (s) => (
                                                                        <button
                                                                            key={
                                                                                s.id
                                                                            }
                                                                            type="button"
                                                                            className="w-full px-3 py-2 text-left text-sm hover:bg-accent"
                                                                            onClick={() => {
                                                                                setSelectedStudentId(
                                                                                    String(
                                                                                        s.id,
                                                                                    ),
                                                                                );
                                                                                setSearch(
                                                                                    '',
                                                                                );
                                                                            }}
                                                                        >
                                                                            {
                                                                                s.first_name
                                                                            }{' '}
                                                                            {
                                                                                s.last_name
                                                                            }{' '}
                                                                            <span className="text-muted-foreground">
                                                                                —{' '}
                                                                                {
                                                                                    s.document_number
                                                                                }
                                                                            </span>
                                                                        </button>
                                                                    ),
                                                                )
                                                            )}
                                                        </div>
                                                    )}
                                            </div>
                                            <Button
                                                onClick={handleEnroll}
                                                disabled={
                                                    !selectedStudentId ||
                                                    enrolling
                                                }
                                            >
                                                <Plus className="mr-2 h-4 w-4" />
                                                {enrolling
                                                    ? 'Inscribiendo...'
                                                    : 'Inscribir'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Drag & drop masiva */}
                                    <div className="space-y-2 border-t pt-4">
                                        <p className="text-xs font-medium text-muted-foreground">
                                            Importación masiva por Excel
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Solo inscribe estudiantes ya
                                            registrados en el sistema. La
                                            columna requerida es{' '}
                                            <code className="rounded bg-muted px-1 py-0.5 font-mono">
                                                documento
                                            </code>
                                            .
                                        </p>
                                        <DownloadButton
                                            size="sm"
                                            href={EnrollmentController.downloadTemplate.url(
                                                programming,
                                            )}
                                        >
                                            Descargar plantilla
                                        </DownloadButton>

                                        {!import_results?.length ? (
                                            <Form
                                                action={EnrollmentController.importMethod.url(
                                                    programming,
                                                )}
                                                method="post"
                                                encType="multipart/form-data"
                                            >
                                                {({ processing }) => (
                                                    <div className="space-y-2">
                                                        <div
                                                            className={`flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed p-8 transition-colors ${
                                                                dragOver
                                                                    ? 'border-primary bg-primary/5'
                                                                    : 'border-muted-foreground/25 hover:border-muted-foreground/50'
                                                            }`}
                                                            onClick={() =>
                                                                fileInputRef.current?.click()
                                                            }
                                                            onDragOver={(e) => {
                                                                e.preventDefault();
                                                                setDragOver(
                                                                    true,
                                                                );
                                                            }}
                                                            onDragLeave={() =>
                                                                setDragOver(
                                                                    false,
                                                                )
                                                            }
                                                            onDrop={(e) => {
                                                                e.preventDefault();
                                                                setDragOver(
                                                                    false,
                                                                );
                                                                const file =
                                                                    e
                                                                        .dataTransfer
                                                                        .files[0];
                                                                if (
                                                                    file &&
                                                                    fileInputRef.current
                                                                ) {
                                                                    const dt =
                                                                        new DataTransfer();
                                                                    dt.items.add(
                                                                        file,
                                                                    );
                                                                    fileInputRef.current.files =
                                                                        dt.files;
                                                                    setSelectedFile(
                                                                        file,
                                                                    );
                                                                }
                                                            }}
                                                        >
                                                            {selectedFile ? (
                                                                <div className="flex items-center gap-3">
                                                                    <FileSpreadsheet className="h-8 w-8 text-green-600" />
                                                                    <div className="text-left">
                                                                        <p className="text-sm font-medium">
                                                                            {
                                                                                selectedFile.name
                                                                            }
                                                                        </p>
                                                                        <p className="text-xs text-muted-foreground">
                                                                            {(
                                                                                selectedFile.size /
                                                                                1024
                                                                            ).toFixed(
                                                                                1,
                                                                            )}{' '}
                                                                            KB
                                                                        </p>
                                                                    </div>
                                                                    <button
                                                                        type="button"
                                                                        className="ml-2 rounded-full p-1 hover:bg-muted"
                                                                        onClick={(
                                                                            e,
                                                                        ) => {
                                                                            e.stopPropagation();
                                                                            setSelectedFile(
                                                                                null,
                                                                            );
                                                                            if (
                                                                                fileInputRef.current
                                                                            )
                                                                                fileInputRef.current.value =
                                                                                    '';
                                                                        }}
                                                                    >
                                                                        <X className="h-4 w-4 text-muted-foreground" />
                                                                    </button>
                                                                </div>
                                                            ) : (
                                                                <>
                                                                    <Upload className="h-8 w-8 text-muted-foreground" />
                                                                    <div className="text-center">
                                                                        <p className="text-sm font-medium">
                                                                            Arrastra
                                                                            el
                                                                            archivo
                                                                            aquí
                                                                        </p>
                                                                        <p className="text-xs text-muted-foreground">
                                                                            o
                                                                            haz
                                                                            clic
                                                                            para
                                                                            seleccionar
                                                                            (.xlsx,
                                                                            .xls,
                                                                            .csv)
                                                                        </p>
                                                                    </div>
                                                                </>
                                                            )}
                                                        </div>
                                                        <input
                                                            ref={fileInputRef}
                                                            name="file"
                                                            type="file"
                                                            accept=".xlsx,.xls,.csv"
                                                            className="hidden"
                                                            onChange={(e) =>
                                                                setSelectedFile(
                                                                    e.target
                                                                        .files?.[0] ??
                                                                        null,
                                                                )
                                                            }
                                                        />
                                                        {selectedFile && (
                                                            <Button
                                                                type="submit"
                                                                className="w-full"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                <Upload className="mr-2 h-4 w-4" />
                                                                {processing
                                                                    ? 'Procesando...'
                                                                    : 'Importar archivo'}
                                                            </Button>
                                                        )}
                                                    </div>
                                                )}
                                            </Form>
                                        ) : (
                                            <div className="space-y-3">
                                                <div className="flex items-center justify-between">
                                                    <p className="text-sm font-medium">
                                                        Resultado (
                                                        {import_results.length}{' '}
                                                        filas)
                                                    </p>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => {
                                                            setSelectedFile(
                                                                null,
                                                            );
                                                            router.get(
                                                                ProgrammingController.show.url(
                                                                    programming,
                                                                ),
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            );
                                                        }}
                                                    >
                                                        Nueva importación
                                                    </Button>
                                                </div>
                                                <div className="max-h-48 space-y-1 overflow-y-auto rounded-md border p-3">
                                                    {import_results.map((r) => (
                                                        <div
                                                            key={r.row}
                                                            className={`flex items-start gap-2 text-xs ${
                                                                r.status ===
                                                                'error'
                                                                    ? 'text-destructive'
                                                                    : r.status ===
                                                                        'created'
                                                                      ? 'text-green-700 dark:text-green-400'
                                                                      : 'text-muted-foreground'
                                                            }`}
                                                        >
                                                            <span className="shrink-0 font-mono">
                                                                F{r.row}
                                                            </span>
                                                            <span>
                                                                {r.message}
                                                            </span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            )}
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
                                        {enrolledPage.visible.map(
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

                        {programming.enrollments.length > 0 && (
                            <ClientPagination state={enrolledPage} />
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
