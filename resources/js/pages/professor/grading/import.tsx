import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    Download,
    FileSpreadsheet,
    Upload,
    XCircle,
} from 'lucide-react';
import { useRef, useState } from 'react';
import * as GradingController from '@/actions/App/Http/Controllers/Professor/GradingController';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ProfessorLayout from '@/layouts/professor/professor-layout';
import type { BreadcrumbItem } from '@/types';

type ImportResult = {
    row: number;
    status: 'success' | 'error' | 'updated' | 'skipped';
    message: string;
};

type ImportResponse = {
    message: string;
    results: ImportResult[];
};

type Props = {
    programming: { id: number; period: string; group: string | null };
    academicSpace: { id: number; name: string; code: string };
};

const ACCEPTED_TYPES = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'text/csv',
];

const MAX_SIZE_MB = 10;

export default function GradingImport({ programming, academicSpace }: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [dragOver, setDragOver] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [fileError, setFileError] = useState<string | null>(null);
    const [uploading, setUploading] = useState(false);
    const [importResponse, setImportResponse] = useState<ImportResponse | null>(
        null,
    );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/professor/dashboard' },
        {
            title: academicSpace.name,
            href: GradingController.show.url(programming),
        },
        {
            title: 'Importar calificaciones',
            href: GradingController.importPage.url(programming),
        },
    ];

    function validateFile(file: File): string | null {
        if (
            !ACCEPTED_TYPES.includes(file.type) &&
            !file.name.match(/\.(xlsx|xls|csv)$/i)
        ) {
            return 'El archivo debe ser un Excel (.xlsx, .xls) o CSV.';
        }
        if (file.size > MAX_SIZE_MB * 1024 * 1024) {
            return `El archivo no puede superar los ${MAX_SIZE_MB} MB.`;
        }
        return null;
    }

    function handleFileSelect(file: File) {
        const error = validateFile(file);
        if (error) {
            setFileError(error);
            setSelectedFile(null);
            return;
        }
        setFileError(null);
        setSelectedFile(file);
        setImportResponse(null);
    }

    function handleDrop(e: React.DragEvent) {
        e.preventDefault();
        setDragOver(false);
        const file = e.dataTransfer.files[0];
        if (file) handleFileSelect(file);
    }

    async function handleUpload() {
        if (!selectedFile) return;
        setUploading(true);
        setImportResponse(null);

        const formData = new FormData();
        formData.append('file', selectedFile);

        try {
            const { data } = await axios.post<ImportResponse>(
                GradingController.importGrades.url(programming),
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } },
            );
            setImportResponse(data);
        } catch (err: unknown) {
            const axiosErr = err as {
                response?: { data?: { message?: string } };
            };
            const msg =
                axiosErr?.response?.data?.message ??
                'Error al procesar el archivo.';
            setImportResponse({ message: msg, results: [] });
        } finally {
            setUploading(false);
        }
    }

    function downloadErrorLog() {
        if (!importResponse?.results) return;
        const errors = importResponse.results.filter(
            (r) => r.status === 'error',
        );
        const content = errors
            .map((e) => `Fila ${e.row}: ${e.message}`)
            .join('\n');
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `errores_importacion_${programming.period}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    }

    const results = importResponse?.results ?? [];
    const successCount = results.filter((r) => r.status === 'success').length;
    const errorCount = results.filter((r) => r.status === 'error').length;
    const errors = results.filter((r) => r.status === 'error');

    return (
        <ProfessorLayout breadcrumbs={breadcrumbs}>
            <Head title={`Importar calificaciones — ${academicSpace.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Importar calificaciones"
                    description={`${academicSpace.name} · ${academicSpace.code} · ${programming.period}${programming.group ? ` · Grupo ${programming.group}` : ''}`}
                >
                    <Button variant="outline" asChild>
                        <Link href={GradingController.show.url(programming)}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver a calificaciones
                        </Link>
                    </Button>
                </PageHeader>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        {/* Paso 1: Descargar plantilla */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Paso 1 — Descarga la plantilla
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="mb-3 text-sm text-muted-foreground">
                                    La plantilla contiene los estudiantes
                                    inscritos como filas y los resultados
                                    microcurriculares con criterios como
                                    columnas. Las celdas tienen validación con
                                    lista desplegable.
                                </p>
                                <Button asChild variant="outline">
                                    <a
                                        href={GradingController.downloadTemplate.url(
                                            programming,
                                        )}
                                        download
                                    >
                                        <Download className="mr-2 h-4 w-4" />
                                        Descargar plantilla Excel
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>

                        {/* Paso 2: Subir archivo */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Paso 2 — Sube el archivo diligenciado
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Drop zone */}
                                <div
                                    onDragOver={(e) => {
                                        e.preventDefault();
                                        setDragOver(true);
                                    }}
                                    onDragLeave={() => setDragOver(false)}
                                    onDrop={handleDrop}
                                    onClick={() =>
                                        fileInputRef.current?.click()
                                    }
                                    className={`cursor-pointer rounded-lg border-2 border-dashed p-10 text-center transition-colors ${
                                        dragOver
                                            ? 'border-primary bg-primary/5'
                                            : selectedFile
                                              ? 'border-green-400 bg-green-50 dark:border-green-700 dark:bg-green-950/20'
                                              : 'border-muted-foreground/30 hover:border-muted-foreground/50 hover:bg-muted/30'
                                    }`}
                                >
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept=".xlsx,.xls,.csv"
                                        className="hidden"
                                        onChange={(e) => {
                                            const f = e.target.files?.[0];
                                            if (f) handleFileSelect(f);
                                        }}
                                    />
                                    {selectedFile ? (
                                        <div className="flex flex-col items-center gap-2">
                                            <FileSpreadsheet className="h-10 w-10 text-green-600" />
                                            <p className="font-medium text-green-700 dark:text-green-400">
                                                {selectedFile.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {(
                                                    selectedFile.size / 1024
                                                ).toFixed(1)}{' '}
                                                KB · Click para cambiar
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="flex flex-col items-center gap-2 text-muted-foreground">
                                            <Upload className="h-10 w-10" />
                                            <p className="font-medium">
                                                Arrastra el archivo aquí o haz
                                                click para seleccionar
                                            </p>
                                            <p className="text-xs">
                                                Excel (.xlsx, .xls) o CSV ·
                                                Máximo {MAX_SIZE_MB} MB
                                            </p>
                                        </div>
                                    )}
                                </div>

                                {fileError && (
                                    <Alert variant="destructive">
                                        <AlertCircle className="h-4 w-4" />
                                        <AlertDescription>
                                            {fileError}
                                        </AlertDescription>
                                    </Alert>
                                )}

                                <Button
                                    onClick={handleUpload}
                                    disabled={!selectedFile || uploading}
                                    className="w-full"
                                >
                                    {uploading ? (
                                        <span className="mr-2 animate-spin">
                                            ⟳
                                        </span>
                                    ) : (
                                        <Upload className="mr-2 h-4 w-4" />
                                    )}
                                    {uploading
                                        ? 'Procesando archivo...'
                                        : 'Importar calificaciones'}
                                </Button>
                            </CardContent>
                        </Card>

                        {/* Paso 3: Resultados */}
                        {importResponse && (
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        Paso 3 — Resultado de la importación
                                    </CardTitle>
                                    {errorCount > 0 && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={downloadErrorLog}
                                        >
                                            ↓ Descargar log de errores
                                        </Button>
                                    )}
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex gap-4">
                                        {successCount > 0 && (
                                            <div className="flex items-center gap-2 text-green-700 dark:text-green-400">
                                                <CheckCircle2 className="h-5 w-5" />
                                                <span className="text-lg font-bold">
                                                    {successCount}
                                                </span>
                                                <span className="text-sm">
                                                    exitosas
                                                </span>
                                            </div>
                                        )}
                                        {errorCount > 0 && (
                                            <div className="flex items-center gap-2 text-destructive">
                                                <XCircle className="h-5 w-5" />
                                                <span className="text-lg font-bold">
                                                    {errorCount}
                                                </span>
                                                <span className="text-sm">
                                                    con error
                                                </span>
                                            </div>
                                        )}
                                    </div>

                                    <Alert
                                        className={
                                            errorCount === 0
                                                ? 'border-green-300 bg-green-50 dark:bg-green-950/20'
                                                : ''
                                        }
                                    >
                                        {errorCount === 0 ? (
                                            <CheckCircle2 className="h-4 w-4 text-green-600" />
                                        ) : (
                                            <AlertCircle className="h-4 w-4" />
                                        )}
                                        <AlertDescription
                                            className={
                                                errorCount === 0
                                                    ? 'text-green-800 dark:text-green-200'
                                                    : ''
                                            }
                                        >
                                            {importResponse.message}
                                        </AlertDescription>
                                    </Alert>

                                    {errors.length > 0 && (
                                        <div className="max-h-60 space-y-1.5 overflow-y-auto rounded-md border p-3">
                                            {errors.map((e) => (
                                                <div
                                                    key={e.row}
                                                    className="flex gap-2 text-sm"
                                                >
                                                    <Badge
                                                        variant="destructive"
                                                        className="shrink-0"
                                                    >
                                                        Fila {e.row}
                                                    </Badge>
                                                    <span className="text-muted-foreground">
                                                        {e.message}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar de instrucciones */}
                    <div>
                        <Card className="bg-muted/30">
                            <CardHeader>
                                <CardTitle className="text-sm">
                                    Instrucciones
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm text-muted-foreground">
                                <div>
                                    <p className="font-medium text-foreground">
                                        Formato del archivo
                                    </p>
                                    <p>
                                        Descarga la plantilla generada para esta
                                        programación. No modifiques las columnas
                                        ni las filas de encabezado.
                                    </p>
                                </div>
                                <div>
                                    <p className="font-medium text-foreground">
                                        Niveles de desempeño
                                    </p>
                                    <p>
                                        Cada celda debe contener exactamente uno
                                        de los nombres:{' '}
                                        <strong>Insuficiente</strong>,{' '}
                                        <strong>Básico</strong>,{' '}
                                        <strong>Competente</strong> o{' '}
                                        <strong>Destacado</strong>.
                                    </p>
                                </div>
                                <div>
                                    <p className="font-medium text-foreground">
                                        Errores parciales
                                    </p>
                                    <p>
                                        Si algunas filas tienen errores, las
                                        filas válidas se procesan de todas
                                        formas. Puedes corregir el archivo y
                                        volver a importar.
                                    </p>
                                </div>
                                <div>
                                    <p className="font-medium text-foreground">
                                        Sobreescritura
                                    </p>
                                    <p>
                                        Si una calificación ya existe, se
                                        actualizará con el nuevo valor del
                                        archivo.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </ProfessorLayout>
    );
}
