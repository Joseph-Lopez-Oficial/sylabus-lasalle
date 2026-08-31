import { Form, router } from '@inertiajs/react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import {
    AlertTriangle,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Download,
    FileSpreadsheet,
    FileText,
    Plus,
    Save,
    Send,
    Upload,
    Users,
    X,
} from 'lucide-react';
import { useCallback } from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';
import * as AnalysisController from '@/actions/App/Http/Controllers/Professor/AcademicSpaceAnalysisController';
import * as GradingController from '@/actions/App/Http/Controllers/Professor/GradingController';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Alert, AlertDescription } from '@/components/ui/alert';
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
import ProfessorLayout from '@/layouts/professor/professor-layout';
import { formatDecimal } from '@/lib/utils';
import type {
    BreadcrumbItem,
    EvaluationCriterion,
    MicrocurricularLearningOutcome,
    MicrocurricularLearningOutcomeType,
    PerformanceLevel,
} from '@/types';

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Pulls a human-readable message out of an axios error, preferring the first
 * field-level validation message the backend returned.
 */
function extractErrorMessage(err: unknown, fallback: string): string {
    const response = (
        err as {
            response?: {
                data?: {
                    message?: string;
                    errors?: Record<string, string[]>;
                };
            };
        }
    )?.response;

    const firstFieldError = Object.values(response?.data?.errors ?? {})[0]?.[0];

    return firstFieldError ?? response?.data?.message ?? fallback;
}

// ── Types ─────────────────────────────────────────────────────────────────────

type Enrollment = {
    id: number;
    student_id: number;
    student: { first_name: string; last_name: string; document_number: string };
};

type ExistingGrade = {
    enrollment_id: number;
    microcurricular_learning_outcome_id: number;
    evaluation_criterion_id: number;
    performance_level_id: number;
    observations: string | null;
};

type OutcomeWithType = MicrocurricularLearningOutcome & {
    type: MicrocurricularLearningOutcomeType;
};

type TypeGroup = MicrocurricularLearningOutcomeType & {
    microcurricular_learning_outcomes: OutcomeWithType[];
};

type Completeness = {
    percentage: number;
    total: number;
    completed: number;
    pending: {
        enrollment_id: number;
        microcurricular_learning_outcome_id: number;
        evaluation_criterion_id: number;
    }[];
};

type ImportResult = { row: number; status: string; message: string };

type Props = {
    programming: {
        id: number;
        group: string | null;
        academic_period?: { name: string };
    };
    academicSpace: { id: number; name: string; code: string };
    outcomesByType: TypeGroup[];
    enrollments: Enrollment[];
    criteriaByTypeId: Record<number, EvaluationCriterion[]>;
    performanceLevels: PerformanceLevel[];
    existingGrades: ExistingGrade[];
    completeness: Completeness;
    pendingAnalysisCount: number;
    enrollment_import_results?: ImportResult[];
};

// ── Constants ─────────────────────────────────────────────────────────────────

const ORDER_TO_GRADE: Record<number, number> = {
    1: 1.3,
    2: 2.5,
    3: 3.8,
    4: 5.0,
};

function orderToGrade(order: number): number {
    return ORDER_TO_GRADE[Math.round(order)] ?? order;
}

// ── Grade key helper ──────────────────────────────────────────────────────────

function gradeKey(
    enrollmentId: number,
    outcomeId: number,
    criterionId: number,
) {
    return `${enrollmentId}-${outcomeId}-${criterionId}`;
}

// ── Grading table for one outcome ─────────────────────────────────────────────

type GradingTableProps = {
    outcome: OutcomeWithType;
    enrollments: Enrollment[];
    criteria: EvaluationCriterion[];
    performanceLevels: PerformanceLevel[];
    localGrades: Record<string, number>;
    savedGrades: Record<string, number>;
    onGradeChange: (
        enrollmentId: number,
        outcomeId: number,
        criterionId: number,
        levelId: number,
    ) => void;
    onSave: (outcomeId: number) => void;
    onDiscard: (outcomeId: number) => void;
    saving: boolean;
};

function GradingTable({
    outcome,
    enrollments,
    criteria,
    performanceLevels,
    localGrades,
    savedGrades,
    onGradeChange,
    onSave,
    onDiscard,
    saving,
}: GradingTableProps) {
    const levelOrderMap = useMemo(
        () => Object.fromEntries(performanceLevels.map((l) => [l.id, l.order])),
        [performanceLevels],
    );

    const isOutcomeComplete = enrollments.every((e) =>
        criteria.every((c) => {
            const key = gradeKey(e.id, outcome.id, c.id);
            return !!localGrades[key];
        }),
    );

    const hasUnsavedChanges = enrollments.some((e) =>
        criteria.some((c) => {
            const key = gradeKey(e.id, outcome.id, c.id);
            return localGrades[key] && localGrades[key] !== savedGrades[key];
        }),
    );

    return (
        <div className="space-y-3">
            <div className="overflow-x-auto rounded-md border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left font-medium text-muted-foreground">
                                Estudiante
                            </th>
                            {criteria.map((c) => (
                                <th
                                    key={c.id}
                                    className="px-3 py-2 text-center font-medium text-muted-foreground"
                                >
                                    {c.name}
                                </th>
                            ))}
                            <th className="px-3 py-2 text-center font-medium text-muted-foreground">
                                Nota
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {enrollments.map((enrollment) => {
                            // Promedio real de las notas de cada criterio para este outcome
                            const filledCriteria = criteria.filter((c) => {
                                const key = gradeKey(
                                    enrollment.id,
                                    outcome.id,
                                    c.id,
                                );
                                return !!localGrades[key];
                            });
                            const avgGrade =
                                filledCriteria.length > 0
                                    ? filledCriteria.reduce((sum, c) => {
                                          const key = gradeKey(
                                              enrollment.id,
                                              outcome.id,
                                              c.id,
                                          );
                                          const levelId = localGrades[key];
                                          return (
                                              sum +
                                              orderToGrade(
                                                  levelOrderMap[levelId] ?? 0,
                                              )
                                          );
                                      }, 0) / filledCriteria.length
                                    : null;
                            const total = avgGrade;

                            return (
                                <tr key={enrollment.id} className="border-t">
                                    <td className="px-3 py-2">
                                        <p className="font-medium">
                                            {enrollment.student.first_name}{' '}
                                            {enrollment.student.last_name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {enrollment.student.document_number}
                                        </p>
                                    </td>
                                    {criteria.map((criterion) => {
                                        const key = gradeKey(
                                            enrollment.id,
                                            outcome.id,
                                            criterion.id,
                                        );
                                        const currentValue = localGrades[key];
                                        const savedValue = savedGrades[key];
                                        const isNew =
                                            currentValue &&
                                            currentValue !== savedValue;
                                        const isSaved =
                                            currentValue &&
                                            currentValue === savedValue;

                                        return (
                                            <td
                                                key={criterion.id}
                                                className="px-2 py-1.5"
                                            >
                                                <Select
                                                    value={
                                                        currentValue
                                                            ? String(
                                                                  currentValue,
                                                              )
                                                            : ''
                                                    }
                                                    onValueChange={(v) =>
                                                        onGradeChange(
                                                            enrollment.id,
                                                            outcome.id,
                                                            criterion.id,
                                                            Number(v),
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger
                                                        className={`h-8 min-w-30 text-xs ${
                                                            isSaved
                                                                ? 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-950/30'
                                                                : isNew
                                                                  ? 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/30'
                                                                  : ''
                                                        }`}
                                                    >
                                                        <SelectValue placeholder="—" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {performanceLevels.map(
                                                            (level) => (
                                                                <SelectItem
                                                                    key={
                                                                        level.id
                                                                    }
                                                                    value={String(
                                                                        level.id,
                                                                    )}
                                                                >
                                                                    {level.name}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                        );
                                    })}
                                    <td className="px-3 py-2 text-center">
                                        <span
                                            className={`font-semibold ${total !== null ? 'text-foreground' : 'text-muted-foreground'}`}
                                        >
                                            {total !== null
                                                ? total.toFixed(2)
                                                : '—'}
                                        </span>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3 text-xs text-muted-foreground">
                    <span className="flex items-center gap-1">
                        <span className="inline-block h-3 w-3 rounded border-2 border-green-300 bg-green-50" />
                        Guardado
                    </span>
                    <span className="flex items-center gap-1">
                        <span className="inline-block h-3 w-3 rounded border-2 border-amber-300 bg-amber-50" />
                        Sin guardar
                    </span>
                    <span className="flex items-center gap-1">
                        <span className="inline-block h-3 w-3 rounded border-2 bg-background" />
                        Sin calificar
                    </span>
                </div>
                <div className="flex items-center gap-2">
                    {hasUnsavedChanges && (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => onDiscard(outcome.id)}
                            disabled={saving}
                        >
                            Descartar cambios
                        </Button>
                    )}
                    {!hasUnsavedChanges && isOutcomeComplete && (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => onDiscard(outcome.id)}
                        >
                            Limpiar
                        </Button>
                    )}
                    <Button
                        size="sm"
                        onClick={() => onSave(outcome.id)}
                        disabled={
                            !isOutcomeComplete || saving || !hasUnsavedChanges
                        }
                        className="gap-2"
                    >
                        {saving ? (
                            <span className="animate-spin">⟳</span>
                        ) : (
                            <Save className="h-4 w-4" />
                        )}
                        {saving
                            ? 'Guardando...'
                            : isOutcomeComplete && !hasUnsavedChanges
                              ? 'Guardado ✓'
                              : 'Guardar resultado'}
                    </Button>
                </div>
            </div>

            {!isOutcomeComplete && (
                <p className="text-xs text-muted-foreground">
                    Debes calificar todos los estudiantes en todos los criterios
                    antes de guardar.
                </p>
            )}
        </div>
    );
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function GradingShow({
    programming,
    academicSpace,
    outcomesByType,
    enrollments,
    criteriaByTypeId,
    performanceLevels,
    existingGrades,
    completeness: initialCompleteness,
    pendingAnalysisCount,
    enrollment_import_results,
}: Props) {
    // Build initial grade map from server data
    const initialGrades = useMemo(() => {
        const map: Record<string, number> = {};
        existingGrades.forEach((g) => {
            map[
                gradeKey(
                    g.enrollment_id,
                    g.microcurricular_learning_outcome_id,
                    g.evaluation_criterion_id,
                )
            ] = g.performance_level_id;
        });
        return map;
    }, [existingGrades]);

    const [localGrades, setLocalGrades] =
        useState<Record<string, number>>(initialGrades);
    const [savedGrades, setSavedGrades] =
        useState<Record<string, number>>(initialGrades);
    const [savingOutcome, setSavingOutcome] = useState<number | null>(null);
    const [gradingError, setGradingError] = useState('');
    const [completeness, setCompleteness] = useState(initialCompleteness);
    const [showConfirmConsolidate, setShowConfirmConsolidate] = useState(false);
    const [consolidating, setConsolidating] = useState(false);
    const [enrollSectionOpen, setEnrollSectionOpen] = useState(false);
    const [documentNumber, setDocumentNumber] = useState('');
    const [enrollError, setEnrollError] = useState('');
    const [suggestions, setSuggestions] = useState<
        {
            id: number;
            document_number: string;
            first_name: string;
            last_name: string;
        }[]
    >([]);
    const [selectedSuggestion, setSelectedSuggestion] = useState<{
        id: number;
        document_number: string;
        first_name: string;
        last_name: string;
    } | null>(null);
    const [searchingStudents, setSearchingStudents] = useState(false);
    const [dragOver, setDragOver] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const fileRef = useRef<HTMLInputElement>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Check if there are unsaved changes before navigation
    const hasUnsavedChanges = useMemo(() => {
        return Object.keys(localGrades).some(
            (key) => localGrades[key] !== savedGrades[key],
        );
    }, [localGrades, savedGrades]);

    useEffect(() => {
        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        };
        window.addEventListener('beforeunload', handleBeforeUnload);
        return () =>
            window.removeEventListener('beforeunload', handleBeforeUnload);
    }, [hasUnsavedChanges]);

    function handleGradeChange(
        enrollmentId: number,
        outcomeId: number,
        criterionId: number,
        levelId: number,
    ) {
        const key = gradeKey(enrollmentId, outcomeId, criterionId);
        setLocalGrades((prev) => ({ ...prev, [key]: levelId }));
    }

    function handleDiscardOutcome(outcomeId: number) {
        const criteriaForOutcome = outcomesByType
            .flatMap((t) => t.microcurricular_learning_outcomes)
            .find((o) => o.id === outcomeId);
        const criteriaList = criteriaForOutcome
            ? (criteriaByTypeId[criteriaForOutcome.type_id] ?? [])
            : [];

        setLocalGrades((prev) => {
            const next = { ...prev };
            enrollments.forEach((e) => {
                criteriaList.forEach((c) => {
                    const key = gradeKey(e.id, outcomeId, c.id);
                    // Revert to saved value, or delete if no saved value
                    if (savedGrades[key] !== undefined) {
                        next[key] = savedGrades[key];
                    } else {
                        delete next[key];
                    }
                });
            });
            return next;
        });
    }

    function handleSaveOutcome(outcomeId: number) {
        const outcome = outcomesByType
            .flatMap((t) => t.microcurricular_learning_outcomes)
            .find((o) => o.id === outcomeId);
        const criteriaForOutcome = outcome
            ? (criteriaByTypeId[outcome.type_id] ?? [])
            : [];

        const gradesToSave = enrollments.flatMap((enrollment) =>
            criteriaForOutcome.map((criterion) => {
                const key = gradeKey(enrollment.id, outcomeId, criterion.id);
                return {
                    enrollment_id: enrollment.id,
                    microcurricular_learning_outcome_id: outcomeId,
                    evaluation_criterion_id: criterion.id,
                    performance_level_id: localGrades[key],
                };
            }),
        );

        setSavingOutcome(outcomeId);
        setGradingError('');

        axios
            .post(GradingController.saveGrades.url(programming), {
                grades: gradesToSave,
            })
            .then(() => {
                const newSaved = { ...savedGrades };
                gradesToSave.forEach((g) => {
                    newSaved[
                        gradeKey(
                            g.enrollment_id,
                            g.microcurricular_learning_outcome_id,
                            g.evaluation_criterion_id,
                        )
                    ] = g.performance_level_id;
                });
                setSavedGrades(newSaved);

                const totalCells =
                    outcomesByType
                        .flatMap((t) => t.microcurricular_learning_outcomes)
                        .reduce(
                            (sum, o) =>
                                sum +
                                (criteriaByTypeId[o.type_id]?.length ?? 0),
                            0,
                        ) * enrollments.length;
                const completedCells = Object.keys(newSaved).length;
                const pct =
                    totalCells > 0
                        ? Math.round(
                              (completedCells / totalCells) * 100 * 100,
                          ) / 100
                        : 100;
                setCompleteness((prev) => ({
                    ...prev,
                    percentage: pct,
                    completed: completedCells,
                    total: totalCells,
                }));
            })
            .catch((err: unknown) =>
                setGradingError(
                    extractErrorMessage(
                        err,
                        'No se pudieron guardar las calificaciones. Intenta nuevamente.',
                    ),
                ),
            )
            .finally(() => setSavingOutcome(null));
    }

    const searchStudents = useCallback(
        (q: string) => {
            if (debounceRef.current) clearTimeout(debounceRef.current);
            if (q.length < 2) {
                setSuggestions([]);
                return;
            }
            debounceRef.current = setTimeout(() => {
                setSearchingStudents(true);
                axios
                    .get(GradingController.searchStudents.url(programming), {
                        params: { q },
                    })
                    .then((res) => setSuggestions(res.data))
                    .catch(() => setSuggestions([]))
                    .finally(() => setSearchingStudents(false));
            }, 300);
        },
        [programming],
    );

    function handleEnrollByDocument() {
        const doc =
            selectedSuggestion?.document_number ?? documentNumber.trim();
        if (!doc) return;
        setEnrollError('');
        router.post(
            GradingController.enrollByDocument.url(programming),
            { document_number: doc },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDocumentNumber('');
                    setSelectedSuggestion(null);
                    setSuggestions([]);
                },
                onError: (errors) =>
                    setEnrollError(
                        errors.document_number ?? 'Error al inscribir.',
                    ),
            },
        );
    }

    function handleConsolidate() {
        setConsolidating(true);
        setGradingError('');
        axios
            .post(GradingController.confirmConsolidation.url(programming))
            .then(() =>
                router.visit(
                    `/professor/programmings/${programming.id}/statistics`,
                ),
            )
            .catch((err: unknown) =>
                setGradingError(
                    extractErrorMessage(
                        err,
                        'No se pudo confirmar el consolidado. Intenta nuevamente.',
                    ),
                ),
            )
            .finally(() => {
                setConsolidating(false);
                setShowConfirmConsolidate(false);
            });
    }

    const isFullyComplete = completeness.percentage >= 100;

    const savedOutcomeIds = useMemo(() => {
        const allOutcomes = outcomesByType.flatMap(
            (t) => t.microcurricular_learning_outcomes,
        );
        return new Set(
            allOutcomes
                .filter((outcome) => {
                    const criteriaForType =
                        criteriaByTypeId[outcome.type_id] ?? [];
                    return enrollments.every((e) =>
                        criteriaForType.every((c) => {
                            const key = gradeKey(e.id, outcome.id, c.id);
                            return savedGrades[key] !== undefined;
                        }),
                    );
                })
                .map((o) => o.id),
        );
    }, [savedGrades, outcomesByType, enrollments, criteriaByTypeId]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/professor/dashboard' },
        {
            title: academicSpace.name,
            href: GradingController.show.url(programming),
        },
    ];

    return (
        <ProfessorLayout breadcrumbs={breadcrumbs}>
            <Head title={`Calificaciones — ${academicSpace.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title={academicSpace.name}
                    description={`${academicSpace.code} · ${programming.academic_period?.name ?? ''}${programming.group ? ` · Grupo ${programming.group}` : ''}`}
                >
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={GradingController.downloadTemplate.url(
                                    programming,
                                )}
                                download
                            >
                                <Download className="mr-2 h-4 w-4" />
                                Plantilla
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={GradingController.importPage.url(
                                    programming,
                                )}
                            >
                                ↑ Importar Excel
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={GradingController.downloadInstitutionalReport.url(
                                    programming,
                                )}
                                download
                            >
                                <FileSpreadsheet className="mr-2 h-4 w-4" />
                                Reporte institucional
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={AnalysisController.show.url(programming)}
                            >
                                <FileText className="mr-2 h-4 w-4" />
                                Análisis
                                {pendingAnalysisCount > 0 && (
                                    <Badge
                                        variant="secondary"
                                        className="ml-2"
                                    >
                                        {pendingAnalysisCount}
                                    </Badge>
                                )}
                            </Link>
                        </Button>
                    </div>
                </PageHeader>

                {gradingError && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>{gradingError}</AlertDescription>
                    </Alert>
                )}

                {/* Progreso global */}
                <div className="rounded-lg border p-4">
                    <div className="mb-2 flex items-center justify-between text-sm">
                        <span className="font-medium">
                            Progreso de calificación
                        </span>
                        <span
                            className={`font-bold ${isFullyComplete ? 'text-green-600' : 'text-foreground'}`}
                        >
                            {formatDecimal(completeness.percentage)}%
                            {isFullyComplete && ' ✓'}
                        </span>
                    </div>
                    <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            className={`h-full rounded-full transition-all ${isFullyComplete ? 'bg-green-500' : 'bg-primary'}`}
                            style={{
                                width: `${Math.min(completeness.percentage, 100)}%`,
                            }}
                        />
                    </div>
                    <p className="mt-1.5 text-xs text-muted-foreground">
                        {completeness.completed} de {completeness.total}{' '}
                        calificaciones registradas
                    </p>
                </div>

                {hasUnsavedChanges && (
                    <Alert className="border-amber-300 bg-amber-50 dark:bg-amber-950/20">
                        <AlertTriangle className="h-4 w-4 text-amber-600" />
                        <AlertDescription className="text-amber-800 dark:text-amber-200">
                            Tienes calificaciones sin guardar. Guarda cada
                            resultado antes de salir.
                        </AlertDescription>
                    </Alert>
                )}

                {/* Sección de inscripción de estudiantes */}
                <Card>
                    <CardHeader
                        className="cursor-pointer pb-3 select-none"
                        onClick={() => setEnrollSectionOpen((v) => !v)}
                    >
                        <CardTitle className="flex items-center justify-between text-sm">
                            <span className="flex items-center gap-2">
                                <Users className="h-4 w-4" />
                                Estudiantes inscritos ({enrollments.length})
                            </span>
                            {enrollSectionOpen ? (
                                <ChevronUp className="h-4 w-4 text-muted-foreground" />
                            ) : (
                                <ChevronDown className="h-4 w-4 text-muted-foreground" />
                            )}
                        </CardTitle>
                    </CardHeader>
                    {enrollSectionOpen && (
                        <CardContent className="space-y-4">
                            {/* Individual con autocomplete */}
                            <div className="space-y-1.5">
                                <p className="text-xs font-medium text-muted-foreground">
                                    Inscribir por documento o nombre
                                </p>
                                <div className="flex gap-2">
                                    <div className="relative max-w-xs flex-1">
                                        <Input
                                            placeholder="Busca por documento o nombre..."
                                            value={
                                                selectedSuggestion
                                                    ? `${selectedSuggestion.document_number} — ${selectedSuggestion.first_name} ${selectedSuggestion.last_name}`
                                                    : documentNumber
                                            }
                                            onChange={(e) => {
                                                setDocumentNumber(
                                                    e.target.value,
                                                );
                                                setSelectedSuggestion(null);
                                                setEnrollError('');
                                                searchStudents(e.target.value);
                                            }}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter')
                                                    handleEnrollByDocument();
                                                if (e.key === 'Escape') {
                                                    setSuggestions([]);
                                                    setSelectedSuggestion(null);
                                                }
                                            }}
                                        />
                                        {suggestions.length > 0 &&
                                            !selectedSuggestion && (
                                                <div className="absolute z-10 mt-1 w-full rounded-md border bg-popover shadow-md">
                                                    {searchingStudents && (
                                                        <p className="px-3 py-2 text-xs text-muted-foreground">
                                                            Buscando...
                                                        </p>
                                                    )}
                                                    {suggestions.map((s) => (
                                                        <button
                                                            key={s.id}
                                                            type="button"
                                                            className="w-full px-3 py-2 text-left text-sm hover:bg-accent"
                                                            onClick={() => {
                                                                setSelectedSuggestion(
                                                                    s,
                                                                );
                                                                setDocumentNumber(
                                                                    '',
                                                                );
                                                                setSuggestions(
                                                                    [],
                                                                );
                                                            }}
                                                        >
                                                            <span className="font-mono text-xs">
                                                                {
                                                                    s.document_number
                                                                }
                                                            </span>{' '}
                                                            — {s.first_name}{' '}
                                                            {s.last_name}
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                    </div>
                                    <Button
                                        size="sm"
                                        onClick={handleEnrollByDocument}
                                        disabled={
                                            !documentNumber.trim() &&
                                            !selectedSuggestion
                                        }
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Inscribir
                                    </Button>
                                </div>
                                {enrollError && (
                                    <p className="text-xs text-destructive">
                                        {enrollError}
                                    </p>
                                )}
                            </div>

                            {/* Masiva por Excel con drag & drop */}
                            <div className="space-y-2 border-t pt-4">
                                <p className="text-xs font-medium text-muted-foreground">
                                    Importación masiva por Excel
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Si el estudiante no existe lo crea con los
                                    datos del Excel (requiere: documento,
                                    nombres, apellidos, correo). Si ya existe
                                    solo lo inscribe.
                                </p>
                                <Button variant="outline" size="sm" asChild>
                                    <a
                                        href={GradingController.downloadEnrollmentTemplate.url(
                                            programming,
                                        )}
                                    >
                                        <Download className="mr-2 h-4 w-4" />
                                        Descargar plantilla
                                    </a>
                                </Button>

                                {!enrollment_import_results?.length ? (
                                    <Form
                                        action={GradingController.importEnrollments.url(
                                            programming,
                                        )}
                                        method="post"
                                        encType="multipart/form-data"
                                    >
                                        {({ processing }) => (
                                            <div className="space-y-2">
                                                <div
                                                    className={`flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-6 transition-colors ${
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
                                                            e.dataTransfer
                                                                .files[0];
                                                        if (
                                                            file &&
                                                            fileRef.current
                                                        ) {
                                                            const dt =
                                                                new DataTransfer();
                                                            dt.items.add(file);
                                                            fileRef.current.files =
                                                                dt.files;
                                                            setSelectedFile(
                                                                file,
                                                            );
                                                        }
                                                    }}
                                                >
                                                    {selectedFile ? (
                                                        <div className="flex items-center gap-2">
                                                            <FileSpreadsheet className="h-6 w-6 text-green-600" />
                                                            <div className="text-left">
                                                                <p className="text-xs font-medium">
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
                                                                className="ml-1 rounded-full p-0.5 hover:bg-muted"
                                                                onClick={(
                                                                    e,
                                                                ) => {
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
                                                                <X className="h-3 w-3 text-muted-foreground" />
                                                            </button>
                                                        </div>
                                                    ) : (
                                                        <>
                                                            <Upload className="h-6 w-6 text-muted-foreground" />
                                                            <p className="text-xs text-muted-foreground">
                                                                Arrastra o haz
                                                                clic (.xlsx,
                                                                .xls, .csv)
                                                            </p>
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
                                                            e.target
                                                                .files?.[0] ??
                                                                null,
                                                        )
                                                    }
                                                />
                                                {selectedFile && (
                                                    <Button
                                                        type="submit"
                                                        size="sm"
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
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between">
                                            <p className="text-xs font-medium">
                                                Resultado (
                                                {
                                                    enrollment_import_results.length
                                                }{' '}
                                                filas)
                                            </p>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    setSelectedFile(null);
                                                    router.get(
                                                        GradingController.show.url(
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
                                        <div className="max-h-36 space-y-1 overflow-y-auto rounded-md border p-2">
                                            {enrollment_import_results.map(
                                                (r) => (
                                                    <div
                                                        key={r.row}
                                                        className={`flex items-start gap-2 text-xs ${
                                                            r.status === 'error'
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
                                                        <span>{r.message}</span>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Lista */}
                            {enrollments.length === 0 ? (
                                <EmptyState
                                    title="Sin estudiantes inscritos"
                                    description="Inscribe estudiantes por documento o importa un Excel."
                                    icon={Users}
                                />
                            ) : (
                                <div className="rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead className="border-b bg-muted/50">
                                            <tr>
                                                <th className="px-3 py-2 text-left font-medium">
                                                    Estudiante
                                                </th>
                                                <th className="px-3 py-2 text-left font-medium">
                                                    Documento
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {enrollments.map((e) => (
                                                <tr
                                                    key={e.id}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="px-3 py-2">
                                                        {e.student.first_name}{' '}
                                                        {e.student.last_name}
                                                    </td>
                                                    <td className="px-3 py-2 font-mono text-xs text-muted-foreground">
                                                        {
                                                            e.student
                                                                .document_number
                                                        }
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    )}
                </Card>

                {/* Tabs por tipo de resultado */}
                {outcomesByType.length === 0 ? (
                    <p className="text-muted-foreground">
                        Este espacio académico no tiene resultados
                        microcurriculares configurados.
                    </p>
                ) : (
                    <Tabs defaultValue={String(outcomesByType[0]?.id)}>
                        <TabsList>
                            {outcomesByType.map((typeGroup) => {
                                const typeOutcomeIds =
                                    typeGroup.microcurricular_learning_outcomes.map(
                                        (o) => o.id,
                                    );
                                const typeSavedCount = typeOutcomeIds.filter(
                                    (id) => savedOutcomeIds.has(id),
                                ).length;
                                const typeTotal = typeOutcomeIds.length;

                                return (
                                    <TabsTrigger
                                        key={typeGroup.id}
                                        value={String(typeGroup.id)}
                                        className="gap-2"
                                    >
                                        {typeGroup.name}
                                        <Badge
                                            variant={
                                                typeSavedCount === typeTotal
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                            className="text-xs"
                                        >
                                            {typeSavedCount}/{typeTotal}
                                        </Badge>
                                    </TabsTrigger>
                                );
                            })}
                        </TabsList>

                        {outcomesByType.map((typeGroup) => (
                            <TabsContent
                                key={typeGroup.id}
                                value={String(typeGroup.id)}
                                className="mt-4"
                            >
                                <Accordion
                                    type="single"
                                    collapsible
                                    className="space-y-2"
                                >
                                    {typeGroup.microcurricular_learning_outcomes.map(
                                        (outcome) => {
                                            const isSaved = savedOutcomeIds.has(
                                                outcome.id,
                                            );

                                            return (
                                                <AccordionItem
                                                    key={outcome.id}
                                                    value={String(outcome.id)}
                                                    className="rounded-lg border px-1"
                                                >
                                                    <AccordionTrigger className="px-3 py-3 hover:no-underline">
                                                        <div className="flex flex-1 items-center justify-between pr-4 text-left">
                                                            <span className="line-clamp-2 text-sm font-medium">
                                                                {
                                                                    outcome.description
                                                                }
                                                            </span>
                                                            {isSaved ? (
                                                                <Badge
                                                                    variant="outline"
                                                                    className="ml-3 shrink-0 gap-1 border-green-300 text-green-700"
                                                                >
                                                                    <CheckCircle2 className="h-3 w-3" />
                                                                    Guardado
                                                                </Badge>
                                                            ) : (
                                                                <Badge
                                                                    variant="outline"
                                                                    className="ml-3 shrink-0 text-muted-foreground"
                                                                >
                                                                    Pendiente
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </AccordionTrigger>
                                                    <AccordionContent className="px-3 pb-4">
                                                        <GradingTable
                                                            outcome={outcome}
                                                            enrollments={
                                                                enrollments
                                                            }
                                                            criteria={
                                                                criteriaByTypeId[
                                                                    outcome
                                                                        .type_id
                                                                ] ?? []
                                                            }
                                                            performanceLevels={
                                                                performanceLevels
                                                            }
                                                            localGrades={
                                                                localGrades
                                                            }
                                                            savedGrades={
                                                                savedGrades
                                                            }
                                                            onGradeChange={
                                                                handleGradeChange
                                                            }
                                                            onSave={
                                                                handleSaveOutcome
                                                            }
                                                            onDiscard={
                                                                handleDiscardOutcome
                                                            }
                                                            saving={
                                                                savingOutcome ===
                                                                outcome.id
                                                            }
                                                        />
                                                    </AccordionContent>
                                                </AccordionItem>
                                            );
                                        },
                                    )}
                                </Accordion>
                            </TabsContent>
                        ))}
                    </Tabs>
                )}

                {/* Botón de consolidación final */}
                <div className="rounded-lg border p-4">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <p className="font-medium">
                                Confirmación del consolidado
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {isFullyComplete
                                    ? 'Todas las calificaciones están registradas. Puedes confirmar el consolidado.'
                                    : `Faltan ${completeness.total - completeness.completed} calificaciones por registrar.`}
                            </p>
                        </div>
                        <Button
                            disabled={!isFullyComplete || hasUnsavedChanges}
                            onClick={() => setShowConfirmConsolidate(true)}
                            className="shrink-0 gap-2"
                        >
                            <Send className="h-4 w-4" />
                            Confirmar consolidado
                        </Button>
                    </div>
                </div>
            </div>

            <ConfirmDialog
                open={showConfirmConsolidate}
                onOpenChange={setShowConfirmConsolidate}
                title="Confirmar consolidado de calificaciones"
                description="Al confirmar el consolidado, podrás ver las estadísticas de la programación. Esta acción indica que las calificaciones están completas."
                confirmLabel="Confirmar y ver estadísticas"
                variant="default"
                loading={consolidating}
                onConfirm={handleConsolidate}
            />
        </ProfessorLayout>
    );
}
