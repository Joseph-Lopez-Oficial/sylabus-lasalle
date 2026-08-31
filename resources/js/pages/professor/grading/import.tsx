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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
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

/** A difference between the analysis in the file and the one already stored. */
type AnalysisConflict = {
    outcome_code: string;
    field: 'outcome_performance' | 'academic_space_performance' | 'improvement_proposals';
    stored: string | null;
    incoming: string;
};

type InstitutionalResponse = {
    message: string;
    saved: number;
    skipped: number;
    errors: { sheet: string; row: string; message: string }[];
    analysis_conflicts: AnalysisConflict[];
    token: string | null;
};

type Props = {
    programming: {
        id: number;
        group: string | null;
        academic_period?: { name: string };
    };
    academicSpace: { id: number; name: string; code: string };
};

const ACCEPTED_TYPES = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'text/csv',
];

const MAX_SIZE_MB = 10;

/** The three open questions, worded as the institutional format asks them. */
const ANALYSIS_LABELS: Record<AnalysisConflict['field'], string> = {
    outcome_performance:
        'Desempeño del grupo con relación al Resultado de Aprendizaje',
    academic_space_performance:
        'Desempeño del grupo con relación al espacio académico',
    improvement_proposals: 'Análisis y propuestas de mejora',
};

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

/** Reads the message an errored request carries, whatever its shape. */
function errorMessage(err: unknown, fallback: string): string {
    const axiosErr = err as {
        response?: { data?: { message?: string; errors?: Record<string, string[]> } };
    };

    return (
        axiosErr?.response?.data?.errors?.file?.[0] ??
        axiosErr?.response?.data?.message ??
        fallback
    );
}

/** The shared drop zone both uploads use. */
function DropZone({
    file,
    onSelect,
}: {
    file: File | null;
    onSelect: (file: File) => void;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [dragOver, setDragOver] = useState(false);

    return (
        <div
            onDragOver={(e) => {
                e.preventDefault();
                setDragOver(true);
            }}
            onDragLeave={() => setDragOver(false)}
            onDrop={(e) => {
                e.preventDefault();
                setDragOver(false);
                const dropped = e.dataTransfer.files[0];
                if (dropped) onSelect(dropped);
            }}
            onClick={() => inputRef.current?.click()}
            className={`cursor-pointer rounded-lg border-2 border-dashed p-10 text-center transition-colors ${
                dragOver
                    ? 'border-primary bg-primary/5'
                    : file
                      ? 'border-green-400 bg-green-50 dark:border-green-700 dark:bg-green-950/20'
                      : 'border-muted-foreground/30 hover:border-muted-foreground/50 hover:bg-muted/30'
            }`}
        >
            <input
                ref={inputRef}
                type="file"
                accept=".xlsx,.xls,.csv"
                className="hidden"
                onChange={(e) => {
                    const selected = e.target.files?.[0];
                    if (selected) onSelect(selected);
                }}
            />
            {file ? (
                <div className="flex flex-col items-center gap-2">
                    <FileSpreadsheet className="h-10 w-10 text-green-600" />
                    <p className="font-medium text-green-700 dark:text-green-400">
                        {file.name}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {(file.size / 1024).toFixed(1)} KB · Click para cambiar
                    </p>
                </div>
            ) : (
                <div className="flex flex-col items-center gap-2 text-muted-foreground">
                    <Upload className="h-10 w-10" />
                    <p className="font-medium">
                        Arrastra el archivo aquí o haz click para seleccionar
                    </p>
                    <p className="text-xs">
                        Excel (.xlsx, .xls) o CSV · Máximo {MAX_SIZE_MB} MB
                    </p>
                </div>
            )}
        </div>
    );
}

export default function GradingImport({ programming, academicSpace }: Props) {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [fileError, setFileError] = useState<string | null>(null);
    const [uploading, setUploading] = useState(false);
    const [importResponse, setImportResponse] = useState<ImportResponse | null>(
        null,
    );

    const [reportFile, setReportFile] = useState<File | null>(null);
    const [reportError, setReportError] = useState<string | null>(null);
    const [reportUploading, setReportUploading] = useState(false);
    const [reportResponse, setReportResponse] =
        useState<InstitutionalResponse | null>(null);
    const [applyingAnalysis, setApplyingAnalysis] = useState(false);
    const [analysisMessage, setAnalysisMessage] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/professor/dashboard' },
        {
            title: academicSpace.name,
            href: GradingController.show.url(programming),
        },
        {
            title: 'Archivos Excel',
            href: GradingController.importPage.url(programming),
        },
    ];

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

    function handleReportSelect(file: File) {
        const error = validateFile(file);
        if (error) {
            setReportError(error);
            setReportFile(null);
            return;
        }
        setReportError(null);
        setReportFile(file);
        setReportResponse(null);
        setAnalysisMessage(null);
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
            setImportResponse({
                message: errorMessage(err, 'Error al procesar el archivo.'),
                results: [],
            });
        } finally {
            setUploading(false);
        }
    }

    async function handleReportUpload() {
        if (!reportFile) return;
        setReportUploading(true);
        setReportResponse(null);
        setReportError(null);
        setAnalysisMessage(null);

        const formData = new FormData();
        formData.append('file', reportFile);

        try {
            const { data } = await axios.post<InstitutionalResponse>(
                GradingController.importInstitutionalReport.url(programming),
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } },
            );
            setReportResponse(data);
        } catch (err: unknown) {
            setReportError(
                errorMessage(err, 'Error al procesar el reporte institucional.'),
            );
        } finally {
            setReportUploading(false);
        }
    }

    async function handleApplyAnalysis() {
        if (!reportResponse?.token) return;
        setApplyingAnalysis(true);

        try {
            const { data } = await axios.post<{ message: string }>(
                GradingController.applyInstitutionalAnalysis.url(programming),
                { token: reportResponse.token },
            );
            setAnalysisMessage(data.message);
            // The file is consumed on confirmation, so the offer must go away.
            setReportResponse({
                ...reportResponse,
                token: null,
                analysis_conflicts: [],
            });
        } catch (err: unknown) {
            setAnalysisMessage(
                errorMessage(err, 'No se pudo reemplazar el análisis.'),
            );
        } finally {
            setApplyingAnalysis(false);
        }
    }

    function discardAnalysis() {
        if (!reportResponse) return;
        setAnalysisMessage('Se conservó el análisis guardado en el sistema.');
        setReportResponse({
            ...reportResponse,
            token: null,
            analysis_conflicts: [],
        });
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
        a.download = `errores_importacion_${programming.academic_period?.name ?? ''}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    }

    const results = importResponse?.results ?? [];
    const successCount = results.filter((r) => r.status === 'success').length;
    const errorCount = results.filter((r) => r.status === 'error').length;
    const errors = results.filter((r) => r.status === 'error');

    const conflicts = reportResponse?.analysis_conflicts ?? [];

    return (
        <ProfessorLayout breadcrumbs={breadcrumbs}>
            <Head title={`Archivos Excel — ${academicSpace.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Archivos Excel"
                    description={`${academicSpace.name} · ${academicSpace.code} · ${programming.academic_period?.name ?? ''}${programming.group ? ` · Grupo ${programming.group}` : ''}`}
                >
                    <Button variant="outline" asChild>
                        <Link href={GradingController.show.url(programming)}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver a calificaciones
                        </Link>
                    </Button>
                </PageHeader>

                <Tabs defaultValue="template">
                    <TabsList>
                        <TabsTrigger value="template">
                            Plantilla de calificaciones
                        </TabsTrigger>
                        <TabsTrigger value="institutional">
                            Reporte institucional
                        </TabsTrigger>
                    </TabsList>

                    {/* ── Plantilla simple ─────────────────────────────── */}
                    <TabsContent value="template">
                        <div className="grid gap-6 lg:grid-cols-3">
                            <div className="space-y-4 lg:col-span-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-sm font-medium text-muted-foreground">
                                            Paso 1 — Descarga la plantilla
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="mb-3 text-sm text-muted-foreground">
                                            La plantilla contiene los
                                            estudiantes inscritos como filas y
                                            los resultados microcurriculares con
                                            criterios como columnas. Las celdas
                                            tienen validación con lista
                                            desplegable.
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

                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-sm font-medium text-muted-foreground">
                                            Paso 2 — Sube el archivo
                                            diligenciado
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <DropZone
                                            file={selectedFile}
                                            onSelect={handleFileSelect}
                                        />

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
                                            disabled={
                                                !selectedFile || uploading
                                            }
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

                                {importResponse && (
                                    <Card>
                                        <CardHeader className="flex flex-row items-center justify-between">
                                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                                Paso 3 — Resultado de la
                                                importación
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
                                                Descarga la plantilla generada
                                                para esta programación. No
                                                modifiques las columnas ni las
                                                filas de encabezado.
                                            </p>
                                        </div>
                                        <div>
                                            <p className="font-medium text-foreground">
                                                Niveles de desempeño
                                            </p>
                                            <p>
                                                Cada celda debe contener
                                                exactamente uno de los nombres:{' '}
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
                                                Si algunas filas tienen errores,
                                                las filas válidas se procesan de
                                                todas formas. Puedes corregir el
                                                archivo y volver a importar.
                                            </p>
                                        </div>
                                        <div>
                                            <p className="font-medium text-foreground">
                                                Sobreescritura
                                            </p>
                                            <p>
                                                Si una calificación ya existe,
                                                se actualizará con el nuevo
                                                valor del archivo.
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </TabsContent>

                    {/* ── Reporte institucional ────────────────────────── */}
                    <TabsContent value="institutional">
                        <div className="grid gap-6 lg:grid-cols-3">
                            <div className="space-y-4 lg:col-span-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-sm font-medium text-muted-foreground">
                                            Paso 1 — Descarga el reporte
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="mb-3 text-sm text-muted-foreground">
                                            Es el archivo en el formato de la
                                            coordinación, con el consolidado,
                                            una hoja por resultado de
                                            aprendizaje y el análisis del
                                            espacio académico. Puedes
                                            descargarlo aunque las
                                            calificaciones estén incompletas,
                                            diligenciarlo y volver a subirlo
                                            aquí.
                                        </p>
                                        <Button asChild variant="outline">
                                            <a
                                                href={GradingController.downloadInstitutionalReport.url(
                                                    programming,
                                                )}
                                                download
                                            >
                                                <Download className="mr-2 h-4 w-4" />
                                                Descargar reporte institucional
                                            </a>
                                        </Button>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-sm font-medium text-muted-foreground">
                                            Paso 2 — Sube el reporte
                                            diligenciado
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <DropZone
                                            file={reportFile}
                                            onSelect={handleReportSelect}
                                        />

                                        {reportError && (
                                            <Alert variant="destructive">
                                                <AlertCircle className="h-4 w-4" />
                                                <AlertDescription>
                                                    {reportError}
                                                </AlertDescription>
                                            </Alert>
                                        )}

                                        <Button
                                            onClick={handleReportUpload}
                                            disabled={
                                                !reportFile || reportUploading
                                            }
                                            className="w-full"
                                        >
                                            {reportUploading ? (
                                                <span className="mr-2 animate-spin">
                                                    ⟳
                                                </span>
                                            ) : (
                                                <Upload className="mr-2 h-4 w-4" />
                                            )}
                                            {reportUploading
                                                ? 'Procesando reporte...'
                                                : 'Importar reporte institucional'}
                                        </Button>
                                    </CardContent>
                                </Card>

                                {reportResponse && (
                                    <Card>
                                        <CardHeader>
                                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                                Paso 3 — Resultado de la
                                                importación
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            <div className="flex gap-4">
                                                <div className="flex items-center gap-2 text-green-700 dark:text-green-400">
                                                    <CheckCircle2 className="h-5 w-5" />
                                                    <span className="text-lg font-bold">
                                                        {reportResponse.saved}
                                                    </span>
                                                    <span className="text-sm">
                                                        registradas
                                                    </span>
                                                </div>
                                                {reportResponse.skipped > 0 && (
                                                    <div className="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                                                        <AlertCircle className="h-5 w-5" />
                                                        <span className="text-lg font-bold">
                                                            {
                                                                reportResponse.skipped
                                                            }
                                                        </span>
                                                        <span className="text-sm">
                                                            omitidas
                                                        </span>
                                                    </div>
                                                )}
                                            </div>

                                            <Alert
                                                className={
                                                    reportResponse.skipped === 0
                                                        ? 'border-green-300 bg-green-50 dark:bg-green-950/20'
                                                        : ''
                                                }
                                            >
                                                {reportResponse.skipped ===
                                                0 ? (
                                                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                                                ) : (
                                                    <AlertCircle className="h-4 w-4" />
                                                )}
                                                <AlertDescription
                                                    className={
                                                        reportResponse.skipped ===
                                                        0
                                                            ? 'text-green-800 dark:text-green-200'
                                                            : ''
                                                    }
                                                >
                                                    {reportResponse.message}
                                                </AlertDescription>
                                            </Alert>

                                            {reportResponse.errors.length >
                                                0 && (
                                                <div className="max-h-60 space-y-1.5 overflow-y-auto rounded-md border p-3">
                                                    {reportResponse.errors.map(
                                                        (e, index) => (
                                                            <div
                                                                key={`${e.sheet}-${e.row}-${index}`}
                                                                className="flex gap-2 text-sm"
                                                            >
                                                                <Badge
                                                                    variant="outline"
                                                                    className="shrink-0"
                                                                >
                                                                    {e.sheet}
                                                                </Badge>
                                                                <span className="text-muted-foreground">
                                                                    {e.message}
                                                                </span>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                )}

                                {analysisMessage && (
                                    <Alert>
                                        <CheckCircle2 className="h-4 w-4" />
                                        <AlertDescription>
                                            {analysisMessage}
                                        </AlertDescription>
                                    </Alert>
                                )}

                                {conflicts.length > 0 && (
                                    <Card className="border-amber-300 dark:border-amber-800">
                                        <CardHeader>
                                            <CardTitle className="text-sm font-medium">
                                                El análisis del archivo difiere
                                                del guardado
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            <p className="text-sm text-muted-foreground">
                                                Las calificaciones ya se
                                                registraron. El análisis no se
                                                tocó: revisa las diferencias y
                                                decide con cuál te quedas.
                                            </p>

                                            {conflicts.map(
                                                (conflict, index) => (
                                                    <div
                                                        key={`${conflict.outcome_code}-${conflict.field}-${index}`}
                                                        className="space-y-2 rounded-md border p-3"
                                                    >
                                                        <div className="flex items-center gap-2">
                                                            <Badge variant="secondary">
                                                                {
                                                                    conflict.outcome_code
                                                                }
                                                            </Badge>
                                                            <span className="text-sm font-medium">
                                                                {
                                                                    ANALYSIS_LABELS[
                                                                        conflict
                                                                            .field
                                                                    ]
                                                                }
                                                            </span>
                                                        </div>
                                                        <div className="grid gap-3 md:grid-cols-2">
                                                            <div>
                                                                <p className="mb-1 text-xs font-medium text-muted-foreground">
                                                                    Guardado en
                                                                    el sistema
                                                                </p>
                                                                <p className="rounded bg-muted/50 p-2 text-sm whitespace-pre-wrap">
                                                                    {conflict.stored ??
                                                                        'Sin texto guardado.'}
                                                                </p>
                                                            </div>
                                                            <div>
                                                                <p className="mb-1 text-xs font-medium text-muted-foreground">
                                                                    En el
                                                                    archivo
                                                                </p>
                                                                <p className="rounded bg-amber-50 p-2 text-sm whitespace-pre-wrap dark:bg-amber-950/20">
                                                                    {
                                                                        conflict.incoming
                                                                    }
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ),
                                            )}

                                            <div className="flex gap-2">
                                                <Button
                                                    onClick={
                                                        handleApplyAnalysis
                                                    }
                                                    disabled={
                                                        applyingAnalysis ||
                                                        !reportResponse?.token
                                                    }
                                                >
                                                    {applyingAnalysis
                                                        ? 'Reemplazando...'
                                                        : 'Reemplazar con el del archivo'}
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    onClick={discardAnalysis}
                                                    disabled={applyingAnalysis}
                                                >
                                                    Conservar el guardado
                                                </Button>
                                            </div>
                                        </CardContent>
                                    </Card>
                                )}
                            </div>

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
                                                Qué archivo es
                                            </p>
                                            <p>
                                                El del formato de la
                                                coordinación, no la plantilla de
                                                calificaciones. Sube el mismo
                                                que descargaste aquí, sin
                                                cambiar hojas ni filas de
                                                encabezado.
                                            </p>
                                        </div>
                                        <div>
                                            <p className="font-medium text-foreground">
                                                Programación correcta
                                            </p>
                                            <p>
                                                El archivo declara su espacio
                                                académico y su período. Si no
                                                corresponden a esta
                                                programación, se rechaza
                                                completo.
                                            </p>
                                        </div>
                                        <div>
                                            <p className="font-medium text-foreground">
                                                Quién cuenta
                                            </p>
                                            <p>
                                                Solo se registran las
                                                calificaciones de los
                                                estudiantes inscritos. Una fila
                                                añadida a mano para alguien
                                                ajeno se omite y se informa.
                                            </p>
                                        </div>
                                        <div>
                                            <p className="font-medium text-foreground">
                                                Análisis
                                            </p>
                                            <p>
                                                Nunca se sobrescribe sin tu
                                                confirmación. Si el texto del
                                                archivo difiere del guardado, se
                                                te muestran ambos para que
                                                elijas.
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </ProfessorLayout>
    );
}
