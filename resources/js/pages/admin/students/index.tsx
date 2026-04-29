import { Form, Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import {
    Download,
    FileSpreadsheet,
    Pencil,
    Plus,
    Power,
    Upload,
    X,
} from 'lucide-react';
import { useRef, useState } from 'react';
import * as StudentController from '@/actions/App/Http/Controllers/Admin/StudentController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/admin-layout';
import type { BreadcrumbItem, PaginatedResponse, Student } from '@/types';

type ImportResult = { row: number; status: string; message: string };

type Props = {
    students: PaginatedResponse<Student>;
    filters: { search?: string };
    import_results?: ImportResult[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Estudiantes', href: StudentController.index.url() },
];

export default function StudentsIndex({
    students,
    filters,
    import_results,
}: Props) {
    const [toggleTarget, setToggleTarget] = useState<Student | null>(null);
    const [toggling, setToggling] = useState(false);
    const [showImport, setShowImport] = useState(!!import_results?.length);
    const [dragOver, setDragOver] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const fileRef = useRef<HTMLInputElement>(null);

    function handleToggle() {
        if (!toggleTarget) return;
        setToggling(true);
        router.patch(
            StudentController.toggleStatus.url(toggleTarget),
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

    const columns: ColumnDef<Student, unknown>[] = [
        {
            accessorKey: 'document_number',
            header: 'Documento',
            cell: ({ row }) => (
                <span className="font-mono text-sm">
                    {row.original.document_number}
                </span>
            ),
        },
        {
            id: 'full_name',
            header: 'Nombre',
            cell: ({ row }) =>
                `${row.original.first_name} ${row.original.last_name}`,
        },
        { accessorKey: 'email', header: 'Correo electrónico' },
        {
            accessorKey: 'is_active',
            header: 'Estado',
            cell: ({ row }) => (
                <StatusBadge isActive={row.original.is_active} />
            ),
        },
        {
            id: 'actions',
            header: '',
            cell: ({ row }) => (
                <div className="flex items-center justify-end gap-1">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={StudentController.edit.url(row.original)}>
                            <Pencil className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => setToggleTarget(row.original)}
                    >
                        <Power className="h-4 w-4" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Estudiantes" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Estudiantes"
                    description="Gestión de estudiantes registrados en el sistema"
                >
                    <Button variant="outline" asChild>
                        <a href={StudentController.downloadTemplate.url()}>
                            <Download className="mr-2 h-4 w-4" />
                            Plantilla
                        </a>
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() => setShowImport((v) => !v)}
                    >
                        <Upload className="mr-2 h-4 w-4" />
                        Importar
                    </Button>
                    <Button asChild>
                        <Link href={StudentController.create.url()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo estudiante
                        </Link>
                    </Button>
                </PageHeader>

                {showImport && (
                    <Card className="max-w-2xl">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm">
                                Importar estudiantes por Excel
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-sm text-muted-foreground">
                                Columnas requeridas:{' '}
                                <code className="rounded bg-muted px-1 py-0.5 font-mono text-xs">
                                    documento, nombres, apellidos, correo,
                                    telefono
                                </code>
                                . Si el documento ya existe se actualiza; si el
                                correo pertenece a otro documento se reporta
                                error.
                            </p>

                            {!import_results?.length ? (
                                <Form
                                    action={StudentController.importMethod.url()}
                                    method="post"
                                    encType="multipart/form-data"
                                >
                                    {({ processing }) => (
                                        <div className="space-y-3">
                                            {/* Zona de drag & drop */}
                                            <div
                                                className={`flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed p-8 transition-colors ${
                                                    dragOver
                                                        ? 'border-primary bg-primary/5'
                                                        : 'border-muted-foreground/25 hover:border-muted-foreground/50'
                                                }`}
                                                onClick={() =>
                                                    fileRef.current?.click()
                                                }
                                                onDragOver={(e) => {
                                                    e.preventDefault();
                                                    setDragOver(true);
                                                }}
                                                onDragLeave={() =>
                                                    setDragOver(false)
                                                }
                                                onDrop={(e) => {
                                                    e.preventDefault();
                                                    setDragOver(false);
                                                    const file =
                                                        e.dataTransfer.files[0];
                                                    if (
                                                        file &&
                                                        fileRef.current
                                                    ) {
                                                        const dt =
                                                            new DataTransfer();
                                                        dt.items.add(file);
                                                        fileRef.current.files =
                                                            dt.files;
                                                        setSelectedFile(file);
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
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                setSelectedFile(
                                                                    null,
                                                                );
                                                                if (
                                                                    fileRef.current
                                                                )
                                                                    fileRef.current.value =
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
                                                                Arrastra el
                                                                archivo aquí
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                o haz clic para
                                                                seleccionar
                                                                (.xlsx, .xls,
                                                                .csv)
                                                            </p>
                                                        </div>
                                                    </>
                                                )}
                                            </div>
                                            <input
                                                ref={fileRef}
                                                name="file"
                                                type="file"
                                                accept=".xlsx,.xls,.csv"
                                                className="hidden"
                                                onChange={(e) =>
                                                    setSelectedFile(
                                                        e.target.files?.[0] ??
                                                            null,
                                                    )
                                                }
                                            />
                                            {selectedFile && (
                                                <Button
                                                    type="submit"
                                                    className="w-full"
                                                    disabled={processing}
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
                                            Resultado de la importación (
                                            {import_results.length} filas)
                                        </p>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                setSelectedFile(null);
                                                router.get(
                                                    StudentController.index.url(),
                                                    {},
                                                    { preserveScroll: true },
                                                );
                                            }}
                                        >
                                            Nueva importación
                                        </Button>
                                    </div>
                                    <div className="max-h-56 space-y-1 overflow-y-auto rounded-md border p-3">
                                        {import_results.map((r) => (
                                            <div
                                                key={r.row}
                                                className={`flex items-start gap-2 text-xs ${
                                                    r.status === 'error'
                                                        ? 'text-destructive'
                                                        : r.status === 'created'
                                                          ? 'text-green-700 dark:text-green-400'
                                                          : 'text-muted-foreground'
                                                }`}
                                            >
                                                <span className="shrink-0 font-mono">
                                                    F{r.row}
                                                </span>
                                                <span>{r.message}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                <DataTable
                    data={students}
                    columns={columns}
                    filters={filters}
                    searchPlaceholder="Buscar por nombre o documento..."
                />
            </div>
            <ConfirmDialog
                open={!!toggleTarget}
                onOpenChange={(open) => {
                    if (!open) setToggleTarget(null);
                }}
                title={
                    toggleTarget?.is_active
                        ? 'Desactivar estudiante'
                        : 'Activar estudiante'
                }
                description={`¿Confirmas ${toggleTarget?.is_active ? 'desactivar' : 'activar'} a "${toggleTarget?.first_name} ${toggleTarget?.last_name}"?`}
                confirmLabel={
                    toggleTarget?.is_active ? 'Desactivar' : 'Activar'
                }
                variant={toggleTarget?.is_active ? 'destructive' : 'default'}
                loading={toggling}
                onConfirm={handleToggle}
            />
        </AdminLayout>
    );
}
