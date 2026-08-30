import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, CheckCircle2, Info, Save } from 'lucide-react';
import { useState } from 'react';
import * as AnalysisController from '@/actions/App/Http/Controllers/Professor/AcademicSpaceAnalysisController';
import * as GradingController from '@/actions/App/Http/Controllers/Professor/GradingController';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import ProfessorLayout from '@/layouts/professor/professor-layout';
import { formatDecimal } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type OutcomeAnalysis = {
    outcome_performance: string | null;
    academic_space_performance: string | null;
    improvement_proposals: string | null;
};

type Outcome = {
    id: number;
    code: string;
    description: string;
    type: { id: number; name: string } | null;
    average: number | null;
    analysis: OutcomeAnalysis | null;
};

type Props = {
    programming: { id: number; group: string | null };
    academicSpace: { id: number; code: string; name: string } | null;
    academicPeriod: { id: number; name: string } | null;
    outcomes: Outcome[];
    canEdit: boolean;
};

/** The three open questions, in the order the institutional format asks them. */
const QUESTIONS = [
    {
        field: 'outcome_performance' as const,
        label: 'Describa el desempeño del grupo con relación al Resultado de Aprendizaje',
    },
    {
        field: 'academic_space_performance' as const,
        label: 'Describa el desempeño del grupo con relación al espacio académico',
    },
    {
        field: 'improvement_proposals' as const,
        label: '¿Cuál es el análisis y qué propuestas de mejora se sugieren?',
    },
];

type FormState = Record<number, OutcomeAnalysis>;

function initialState(outcomes: Outcome[]): FormState {
    return Object.fromEntries(
        outcomes.map((o) => [
            o.id,
            {
                outcome_performance: o.analysis?.outcome_performance ?? '',
                academic_space_performance:
                    o.analysis?.academic_space_performance ?? '',
                improvement_proposals: o.analysis?.improvement_proposals ?? '',
            },
        ]),
    );
}

function isWritten(a: OutcomeAnalysis | undefined): boolean {
    if (!a) return false;

    return QUESTIONS.some((q) => (a[q.field] ?? '').trim() !== '');
}

export default function AnalysisShow({
    programming,
    academicSpace,
    academicPeriod,
    outcomes,
    canEdit,
}: Props) {
    const [form, setForm] = useState<FormState>(() => initialState(outcomes));
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saveResult, setSaveResult] = useState<'saved' | 'failed' | null>(
        null,
    );

    const pending = outcomes.filter((o) => !isWritten(form[o.id])).length;

    function update(
        outcomeId: number,
        field: keyof OutcomeAnalysis,
        value: string,
    ) {
        setSaveResult(null);
        setForm((prev) => ({
            ...prev,
            [outcomeId]: { ...prev[outcomeId], [field]: value },
        }));
    }

    function handleSave() {
        setSaving(true);
        router.post(
            AnalysisController.save.url(programming),
            {
                analyses: outcomes.map((o) => ({
                    microcurricular_learning_outcome_id: o.id,
                    ...form[o.id],
                })),
            },
            {
                preserveScroll: true,
                onError: (e) => {
                    setErrors(e as Record<string, string>);
                    setSaveResult('failed');
                },
                onSuccess: () => {
                    setErrors({});
                    setSaveResult('saved');
                },
                onFinish: () => setSaving(false),
            },
        );
    }

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/professor/dashboard' },
        {
            title: academicSpace?.name ?? 'Programación',
            href: GradingController.show.url(programming),
        },
        {
            title: 'Análisis del espacio académico',
            href: AnalysisController.show.url(programming),
        },
    ];

    return (
        <ProfessorLayout breadcrumbs={breadcrumbs}>
            <Head title={`Análisis — ${academicSpace?.name ?? ''}`} />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Análisis del espacio académico"
                    description={`${academicSpace?.name ?? ''}${academicPeriod ? ` · ${academicPeriod.name}` : ''}${programming.group ? ` · Grupo ${programming.group}` : ''}`}
                >
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={GradingController.show.url(programming)}
                            >
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver a calificar
                            </Link>
                        </Button>
                        {canEdit && outcomes.length > 0 && (
                            <Button
                                size="sm"
                                onClick={handleSave}
                                disabled={saving}
                            >
                                <Save className="mr-2 h-4 w-4" />
                                {saving ? 'Guardando…' : 'Guardar análisis'}
                            </Button>
                        )}
                    </div>
                </PageHeader>

                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        El análisis es opcional y no afecta el avance de
                        calificación.
                        {pending > 0
                            ? ` Quedan ${pending} resultado(s) de aprendizaje sin análisis.`
                            : ' Todos los resultados de aprendizaje tienen análisis.'}
                    </AlertDescription>
                </Alert>

                {saveResult === 'saved' && (
                    <Alert className="border-emerald-300 bg-emerald-50 dark:bg-emerald-950/20">
                        <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                        <AlertDescription className="text-emerald-800 dark:text-emerald-200">
                            El análisis se guardó correctamente.
                        </AlertDescription>
                    </Alert>
                )}

                {saveResult === 'failed' && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>
                            {/* Per-field errors arrive keyed as
                            analyses.0.outcome_performance, so the first one is
                            shown instead of a message that says nothing. */}
                            {errors.analyses ??
                                Object.values(errors)[0] ??
                                'No se pudo guardar el análisis. Revise los textos e inténtelo de nuevo.'}
                        </AlertDescription>
                    </Alert>
                )}

                {outcomes.length === 0 ? (
                    <Card>
                        <CardContent className="pt-6">
                            <EmptyState description="Este espacio académico no tiene resultados de aprendizaje para analizar." />
                        </CardContent>
                    </Card>
                ) : (
                    outcomes.map((outcome) => (
                        <Card key={outcome.id}>
                            <CardHeader className="pb-3">
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <CardTitle className="text-base">
                                            {outcome.code}
                                            {outcome.type
                                                ? ` · ${outcome.type.name}`
                                                : ''}
                                        </CardTitle>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {outcome.description}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-2">
                                        {!isWritten(form[outcome.id]) && (
                                            <Badge variant="outline">
                                                Sin análisis
                                            </Badge>
                                        )}
                                        <Badge variant="secondary">
                                            {outcome.average === null
                                                ? 'Sin calificar'
                                                : `Promedio ${formatDecimal(outcome.average)}`}
                                        </Badge>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {QUESTIONS.map((question) => (
                                    <div
                                        key={question.field}
                                        className="grid gap-1.5"
                                    >
                                        <Label
                                            htmlFor={`${question.field}-${outcome.id}`}
                                        >
                                            {question.label}
                                        </Label>
                                        <Textarea
                                            id={`${question.field}-${outcome.id}`}
                                            rows={4}
                                            value={
                                                form[outcome.id]?.[
                                                    question.field
                                                ] ?? ''
                                            }
                                            disabled={!canEdit}
                                            onChange={(e) =>
                                                update(
                                                    outcome.id,
                                                    question.field,
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    ))
                )}

            </div>
        </ProfessorLayout>
    );
}
