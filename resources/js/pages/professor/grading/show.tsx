import { router } from '@inertiajs/react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import {
    AlertTriangle,
    CheckCircle2,
    Download,
    Save,
    Send,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import * as GradingController from '@/actions/App/Http/Controllers/Professor/GradingController';
import { ConfirmDialog } from '@/components/confirm-dialog';
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

type Props = {
    programming: { id: number; period: string; group: string | null };
    academicSpace: { id: number; name: string; code: string };
    outcomesByType: TypeGroup[];
    enrollments: Enrollment[];
    criteria: EvaluationCriterion[];
    performanceLevels: PerformanceLevel[];
    existingGrades: ExistingGrade[];
    completeness: Completeness;
};

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
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {enrollments.map((enrollment) => {
                            const total = criteria.reduce((sum, c) => {
                                const key = gradeKey(
                                    enrollment.id,
                                    outcome.id,
                                    c.id,
                                );
                                const levelId = localGrades[key];
                                return (
                                    sum +
                                    (levelId
                                        ? (levelOrderMap[levelId] ?? 0)
                                        : 0)
                                );
                            }, 0);

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
                                            className={`font-semibold ${total > 0 ? 'text-foreground' : 'text-muted-foreground'}`}
                                        >
                                            {total > 0 ? total : '—'}
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
    criteria,
    performanceLevels,
    existingGrades,
    completeness: initialCompleteness,
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
    const [completeness, setCompleteness] = useState(initialCompleteness);
    const [showConfirmConsolidate, setShowConfirmConsolidate] = useState(false);
    const [consolidating, setConsolidating] = useState(false);

    const allOutcomeIds = useMemo(
        () =>
            outcomesByType.flatMap((t) =>
                t.microcurricular_learning_outcomes.map((o) => o.id),
            ),
        [outcomesByType],
    );

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

    function handleSaveOutcome(outcomeId: number) {
        const gradesToSave = enrollments.flatMap((enrollment) =>
            criteria.map((criterion) => {
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
                    enrollments.length * allOutcomeIds.length * criteria.length;
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
            .catch(console.error)
            .finally(() => setSavingOutcome(null));
    }

    function handleConsolidate() {
        setConsolidating(true);
        axios
            .post(GradingController.confirmConsolidation.url(programming))
            .then(() =>
                router.visit(
                    `/professor/programmings/${programming.id}/statistics`,
                ),
            )
            .catch(console.error)
            .finally(() => {
                setConsolidating(false);
                setShowConfirmConsolidate(false);
            });
    }

    const isFullyComplete = completeness.percentage >= 100;

    const savedOutcomeIds = useMemo(() => {
        return new Set(
            allOutcomeIds.filter((outcomeId) =>
                enrollments.every((e) =>
                    criteria.every((c) => {
                        const key = gradeKey(e.id, outcomeId, c.id);
                        return savedGrades[key] !== undefined;
                    }),
                ),
            ),
        );
    }, [savedGrades, allOutcomeIds, enrollments, criteria]);

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
                    description={`${academicSpace.code} · ${programming.period}${programming.group ? ` · Grupo ${programming.group}` : ''}`}
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
                    </div>
                </PageHeader>

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
                                                            criteria={criteria}
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
