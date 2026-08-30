import { Head } from '@inertiajs/react';
import {
    AlertTriangle,
    ChevronDown,
    ChevronUp,
    CheckCircle2,
    Trophy,
    Zap,
} from 'lucide-react';
import { useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import * as GradingController from '@/actions/App/Http/Controllers/Professor/GradingController';
import * as StatisticsController from '@/actions/App/Http/Controllers/Professor/StatisticsController';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import ProfessorLayout from '@/layouts/professor/professor-layout';
import {
    gradeBadgeClass,
    gradeTextClass,
    levelColor,
    levelLabelForGrade,
    setActiveScale,
} from '@/lib/grading-scale';
import { formatDecimal } from '@/lib/utils';
import type {
    BreadcrumbItem,
    CriterionStats,
    LevelDistribution,
    OutcomeStats,
    ProgrammingStats,
    StudentStats,
} from '@/types';

// ── Types ─────────────────────────────────────────────────────────────────────

type ProgrammingInfo = {
    id: number;
    group: string | null;
    academic_period?: { name: string };
    academic_space: { id: number; name: string; code: string };
    professor: { id: number; first_name: string; last_name: string };
    modality: { id: number; name: string };
};

type Props = {
    programming: ProgrammingInfo;
    statistics: ProgrammingStats;
    completeness: { percentage: number; total: number; completed: number };
};

// ── Constants ─────────────────────────────────────────────────────────────────

const LEVEL_COLOR_LIST = ['#ef4444', '#f97316', '#22c55e', '#3b82f6'];

const TYPE_COLORS: Record<string, string> = {
    Conocimiento: '#6366f1',
    Habilidad: '#10b981',
    Actitud: '#f59e0b',
};
const TYPE_FALLBACK = '#8b5cf6';

// Names, colours and thresholds all come from the configured scale, so
// renaming a level or changing its value from the administration screen is
// reflected here without touching this file.
const gradeColor = gradeTextClass;
const gradeBgClass = gradeBadgeClass;
const levelLabel = levelLabelForGrade;

// ── Shared: Distribution Chart ────────────────────────────────────────────────
// Two-bar horizontal chart: calificaciones individuales + por estudiantes

function DistributionCharts({
    distribution,
    title1 = 'Calificaciones individuales por nivel',
    title2 = 'Estudiantes con al menos un nivel',
}: {
    distribution: LevelDistribution[];
    title1?: string;
    title2?: string;
}) {
    const barData = distribution.map((d) => ({
        name: d.level_name,
        calificaciones: d.count,
        estudiantes: d.student_count,
        pct: d.percentage,
        pctEst: d.student_percentage,
    }));

    return (
        <div className="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm">{title1}</CardTitle>
                    <p className="text-xs text-muted-foreground">
                        Total de asignaciones de nivel por criterio × RA ×
                        estudiante
                    </p>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={180}>
                        <BarChart data={barData} layout="vertical">
                            <CartesianGrid
                                strokeDasharray="3 3"
                                horizontal={false}
                            />
                            <XAxis type="number" tick={{ fontSize: 10 }} />
                            <YAxis
                                type="category"
                                dataKey="name"
                                tick={{ fontSize: 11 }}
                                width={90}
                            />
                            <Tooltip
                                formatter={(v, _, p) => [
                                    `${v} calificaciones (${(p.payload as { pct?: number }).pct ?? 0}%)`,
                                    'Total',
                                ]}
                            />
                            <Bar dataKey="calificaciones" radius={[0, 4, 4, 0]}>
                                {barData.map((d, i) => (
                                    <Cell
                                        key={i}
                                        fill={
                                            levelColor(d.name) ??
                                            LEVEL_COLOR_LIST[i % 4]
                                        }
                                    />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm">{title2}</CardTitle>
                    <p className="text-xs text-muted-foreground">
                        Cuántos estudiantes distintos recibieron cada nivel
                        (puede sumar más del 100%)
                    </p>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={180}>
                        <BarChart data={barData} layout="vertical">
                            <CartesianGrid
                                strokeDasharray="3 3"
                                horizontal={false}
                            />
                            <XAxis type="number" tick={{ fontSize: 10 }} />
                            <YAxis
                                type="category"
                                dataKey="name"
                                tick={{ fontSize: 11 }}
                                width={90}
                            />
                            <Tooltip
                                formatter={(v, _, p) => [
                                    `${v} estudiantes (${(p.payload as { pctEst?: number }).pctEst ?? 0}%)`,
                                    'Estudiantes',
                                ]}
                            />
                            <Bar dataKey="estudiantes" radius={[0, 4, 4, 0]}>
                                {barData.map((d, i) => (
                                    <Cell
                                        key={i}
                                        fill={
                                            levelColor(d.name) ??
                                            LEVEL_COLOR_LIST[i % 4]
                                        }
                                    />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </CardContent>
            </Card>
        </div>
    );
}

// ── Tab 1: Resumen general ────────────────────────────────────────────────────

function SummaryTab({
    summary,
    byStudent,
    onStudentClick,
}: {
    summary: ProgrammingStats['summary'];
    byStudent: StudentStats[];
    onStudentClick: (id: number) => void;
}) {
    const overallGrade = summary.overall_average;
    // Competente ≥ 3.8, Destacado = 5.0 — ambos son "nivel alto"
    const highCount = byStudent.filter((s) => s.final_average >= 3.8).length;
    const belowBasicCount = byStudent.filter(
        (s) => s.final_average < 2.5,
    ).length;

    return (
        <div className="space-y-6">
            {/* KPIs */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardContent className="pt-5 text-center">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Promedio del grupo
                        </p>
                        <p
                            className={`mt-1 text-4xl font-bold ${gradeColor(overallGrade)}`}
                        >
                            {formatDecimal(overallGrade)}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {levelLabel(overallGrade)} · promedio de promedios
                            finales
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="pt-5 text-center">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Estudiantes calificados
                        </p>
                        <p className="mt-1 text-4xl font-bold">
                            {byStudent.length}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            con nota final calculada
                        </p>
                    </CardContent>
                </Card>
                <Card className="border-blue-200">
                    <CardContent className="pt-5 text-center">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Competente o Destacado
                        </p>
                        <p className="mt-1 text-4xl font-bold text-blue-600">
                            {highCount}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            estudiantes · nota ≥ 3.8
                        </p>
                    </CardContent>
                </Card>
                <Card
                    className={
                        belowBasicCount > 0
                            ? 'border-red-200'
                            : 'border-green-200'
                    }
                >
                    <CardContent className="pt-5 text-center">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Bajo nivel Básico
                        </p>
                        <p
                            className={`mt-1 text-4xl font-bold ${belowBasicCount > 0 ? 'text-red-600' : 'text-green-600'}`}
                        >
                            {belowBasicCount}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            estudiantes · nota {'<'} 2.5
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* Distribuciones globales */}
            <DistributionCharts
                distribution={summary.distribution}
                title1="Distribución global de calificaciones por nivel"
                title2="Estudiantes que recibieron cada nivel (global)"
            />

            {/* Top 5 + requieren atención */}
            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Trophy className="h-4 w-4 text-yellow-500" />
                            Top 5 estudiantes
                        </CardTitle>
                        <p className="text-xs text-muted-foreground">
                            Clic para ver detalle
                        </p>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-2">
                            {summary.top_students.map((s, i) => (
                                <button
                                    key={s.enrollment_id}
                                    type="button"
                                    onClick={() =>
                                        onStudentClick(s.enrollment_id)
                                    }
                                    className="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left transition-colors hover:bg-accent"
                                >
                                    <div className="flex items-center gap-3">
                                        <span
                                            className={`flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold text-white ${i === 0 ? 'bg-yellow-400' : i === 1 ? 'bg-gray-400' : i === 2 ? 'bg-amber-600' : 'bg-primary'}`}
                                        >
                                            {i + 1}
                                        </span>
                                        <span className="text-sm font-medium">
                                            {s.student_name}
                                        </span>
                                    </div>
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-xs font-bold ${gradeBgClass(s.final_average)}`}
                                    >
                                        {formatDecimal(s.final_average)}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {summary.below_basic.length > 0 ? (
                    <Card className="border-red-200 bg-red-50/30 dark:border-red-900 dark:bg-red-950/10">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base text-red-700 dark:text-red-400">
                                <AlertTriangle className="h-4 w-4" />
                                Requieren atención
                                <Badge variant="destructive">
                                    {summary.below_basic.length}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {summary.below_basic.map((s) => (
                                    <button
                                        key={s.enrollment_id}
                                        type="button"
                                        onClick={() =>
                                            onStudentClick(s.enrollment_id)
                                        }
                                        className="flex w-full items-center justify-between rounded-lg border border-red-200 bg-white px-3 py-2 text-left transition-colors hover:bg-red-50 dark:border-red-800 dark:bg-transparent"
                                    >
                                        <span className="text-sm text-red-800 dark:text-red-300">
                                            {s.student_name}
                                        </span>
                                        <Badge variant="destructive">
                                            {formatDecimal(s.final_average)}
                                        </Badge>
                                    </button>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <Card className="border-green-200 bg-green-50/30">
                        <CardContent className="pt-6">
                            <p className="text-center text-sm font-medium text-green-700">
                                <CheckCircle2 className="mr-1.5 inline h-4 w-4 text-green-600" />
                                Todos los estudiantes en nivel Básico o superior
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </div>
    );
}

// ── Tab 2: Por Estudiante ─────────────────────────────────────────────────────

function ByStudentTab({
    byStudent,
    initialEnrollmentId,
}: {
    byStudent: StudentStats[];
    byOutcome: OutcomeStats[];
    initialEnrollmentId?: number;
}) {
    const sorted = [...byStudent].sort(
        (a, b) => b.final_average - a.final_average,
    );

    const [selectedId, setSelectedId] = useState<string>(
        initialEnrollmentId
            ? String(initialEnrollmentId)
            : sorted[0]
              ? String(sorted[0].enrollment_id)
              : '',
    );

    const student = byStudent.find(
        (s) => String(s.enrollment_id) === selectedId,
    );

    // Group outcomes by type
    const outcomesByType = student
        ? student.by_outcome.reduce<Record<string, typeof student.by_outcome>>(
              (acc, o) => {
                  const t = o.type_name ?? 'Otros';
                  if (!acc[t]) acc[t] = [];
                  acc[t].push(o);
                  return acc;
              },
              {},
          )
        : {};

    // Bar chart: one bar per RA, colored by type
    const raBarData =
        student?.by_outcome.map((o) => ({
            name: o.outcome_code ?? `RA${o.outcome_id}`,
            fullName: o.outcome_desc,
            nota: o.grade,
            type: o.type_name ?? '',
        })) ?? [];

    return (
        <div className="space-y-4">
            {/* Selector + mini ranking */}
            <div className="flex flex-wrap items-center gap-3">
                <div className="max-w-xs flex-1">
                    <Select value={selectedId} onValueChange={setSelectedId}>
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona un estudiante" />
                        </SelectTrigger>
                        <SelectContent>
                            {sorted.map((s, i) => (
                                <SelectItem
                                    key={s.enrollment_id}
                                    value={String(s.enrollment_id)}
                                >
                                    <span className="flex items-center gap-2">
                                        <span
                                            className={`inline-block h-2 w-2 rounded-full`}
                                            style={{
                                                backgroundColor:
                                                    levelColor(levelLabel(
                                                            s.final_average,
                                                        )) ?? '#ccc',
                                            }}
                                        />
                                        #{i + 1} {s.student_name} —{' '}
                                        {formatDecimal(s.final_average)}
                                    </span>
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <p className="text-xs text-muted-foreground">
                    {byStudent.length} estudiante
                    {byStudent.length !== 1 ? 's' : ''}
                </p>
            </div>

            {student && (
                <div className="space-y-6">
                    {/* Nota final + gráfica de RAs */}
                    <div className="grid gap-6 lg:grid-cols-3">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    {student.student_name}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div
                                    className={`rounded-xl p-4 text-center ${gradeBgClass(student.final_average)}`}
                                >
                                    <p className="text-xs font-medium tracking-wide uppercase opacity-70">
                                        Nota final
                                    </p>
                                    <p className="text-5xl font-bold">
                                        {formatDecimal(student.final_average)}
                                    </p>
                                    <p className="text-xs opacity-70">
                                        {levelLabel(student.final_average)} ·
                                        sobre 5.0
                                    </p>
                                </div>

                                {/* Promedio por tipo de resultado */}
                                {Object.entries(outcomesByType).map(
                                    ([typeName, outcomes]) => {
                                        const typeAvg =
                                            outcomes.reduce(
                                                (s, o) => s + o.grade,
                                                0,
                                            ) / outcomes.length;
                                        return (
                                            <div
                                                key={typeName}
                                                className="rounded-lg border p-3"
                                            >
                                                <div className="mb-0.5 flex items-center justify-between gap-2">
                                                    <span
                                                        className="text-xs font-semibold tracking-wide uppercase"
                                                        style={{
                                                            color:
                                                                TYPE_COLORS[
                                                                    typeName
                                                                ] ??
                                                                TYPE_FALLBACK,
                                                        }}
                                                    >
                                                        {typeName}
                                                    </span>
                                                    <span
                                                        className={`text-sm font-bold ${gradeColor(typeAvg)}`}
                                                    >
                                                        {formatDecimal(typeAvg)}
                                                    </span>
                                                </div>
                                                <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full rounded-full"
                                                        style={{
                                                            width: `${((typeAvg - 1.3) / 3.7) * 100}%`,
                                                            backgroundColor:
                                                                TYPE_COLORS[
                                                                    typeName
                                                                ] ??
                                                                TYPE_FALLBACK,
                                                        }}
                                                    />
                                                </div>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    Promedio de los{' '}
                                                    {outcomes.length} RA
                                                    {outcomes.length !== 1
                                                        ? 's'
                                                        : ''}{' '}
                                                    de tipo {typeName} · cada RA
                                                    es el promedio de sus{' '}
                                                    {outcomes[0]?.by_criterion
                                                        .length ?? '—'}{' '}
                                                    criterios
                                                </p>
                                            </div>
                                        );
                                    },
                                )}

                                {/* Nav por el grupo */}
                                <div>
                                    <p className="mb-1 text-xs text-muted-foreground">
                                        Posición en el grupo
                                    </p>
                                    <div className="flex flex-wrap gap-1">
                                        {sorted.map((s, i) => (
                                            <button
                                                key={s.enrollment_id}
                                                type="button"
                                                title={`${s.student_name}: ${formatDecimal(s.final_average)}`}
                                                onClick={() =>
                                                    setSelectedId(
                                                        String(s.enrollment_id),
                                                    )
                                                }
                                                className={`flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold transition-transform hover:scale-110 ${String(s.enrollment_id) === selectedId ? 'ring-2 ring-primary ring-offset-1' : ''}`}
                                                style={{
                                                    backgroundColor:
                                                        (levelColor(levelLabel(
                                                                s.final_average,
                                                            )) ?? '#ccc') + '22',
                                                    color:
                                                        levelColor(levelLabel(
                                                                s.final_average,
                                                            )) ?? '#ccc',
                                                    border: `1px solid ${levelColor(levelLabel(s.final_average)) ?? '#ccc'}`,
                                                }}
                                            >
                                                {i + 1}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Gráfica de barras por RA */}
                        <Card className="lg:col-span-2">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Nota por resultado de aprendizaje
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">
                                    Promedio de criterios de cada RA · escala
                                    1.3–5.0
                                </p>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={220}>
                                    <BarChart
                                        data={raBarData}
                                        margin={{ bottom: 5 }}
                                    >
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            vertical={false}
                                        />
                                        <XAxis
                                            dataKey="name"
                                            tick={{ fontSize: 11 }}
                                        />
                                        <YAxis
                                            domain={[1, 5]}
                                            tickCount={5}
                                            tick={{ fontSize: 10 }}
                                        />
                                        <Tooltip
                                            formatter={(v) => [
                                                `${formatDecimal(Number(v))} — ${levelLabel(Number(v))}`,
                                                'Nota',
                                            ]}
                                            labelFormatter={(_, payload) =>
                                                (
                                                    payload[0]?.payload as {
                                                        fullName?: string;
                                                    }
                                                )?.fullName ?? ''
                                            }
                                        />
                                        <Bar
                                            dataKey="nota"
                                            radius={[4, 4, 0, 0]}
                                        >
                                            {raBarData.map((entry, i) => (
                                                <Cell
                                                    key={i}
                                                    fill={
                                                        TYPE_COLORS[
                                                            entry.type
                                                        ] ?? TYPE_FALLBACK
                                                    }
                                                />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                                {/* Leyenda de tipos */}
                                <div className="mt-2 flex flex-wrap gap-3">
                                    {Object.entries(TYPE_COLORS).map(
                                        ([t, c]) => (
                                            <span
                                                key={t}
                                                className="flex items-center gap-1 text-xs"
                                            >
                                                <span
                                                    className="inline-block h-2.5 w-2.5 rounded-sm"
                                                    style={{
                                                        backgroundColor: c,
                                                    }}
                                                />
                                                {t}
                                            </span>
                                        ),
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Detalle por RA con criterios */}
                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                            Detalle por resultado de aprendizaje
                        </h3>
                        {Object.entries(outcomesByType).map(
                            ([typeName, outcomes]) => (
                                <div key={typeName}>
                                    <p
                                        className="mb-2 text-xs font-semibold tracking-wide uppercase"
                                        style={{
                                            color:
                                                TYPE_COLORS[typeName] ??
                                                TYPE_FALLBACK,
                                        }}
                                    >
                                        {typeName}
                                    </p>
                                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        {outcomes.map((outcome) => (
                                            <Card
                                                key={outcome.outcome_id}
                                                className="border-l-4"
                                                style={{
                                                    borderLeftColor:
                                                        TYPE_COLORS[typeName] ??
                                                        TYPE_FALLBACK,
                                                }}
                                            >
                                                <CardContent className="pt-3">
                                                    <div className="mb-2 flex items-start justify-between gap-2">
                                                        <p className="text-xs font-semibold">
                                                            {outcome.outcome_code ??
                                                                `RA${outcome.outcome_id}`}
                                                        </p>
                                                        <span
                                                            className={`shrink-0 rounded px-1.5 py-0.5 text-xs font-bold ${gradeBgClass(outcome.grade)}`}
                                                        >
                                                            {formatDecimal(
                                                                outcome.grade,
                                                            )}
                                                        </span>
                                                    </div>
                                                    <p
                                                        className="mb-2 line-clamp-2 text-xs text-muted-foreground"
                                                        title={
                                                            outcome.outcome_desc
                                                        }
                                                    >
                                                        {outcome.outcome_desc}
                                                    </p>
                                                    <div className="space-y-1">
                                                        {outcome.by_criterion.map(
                                                            (c) => (
                                                                <div
                                                                    key={
                                                                        c.criterion_id
                                                                    }
                                                                    className="flex items-center justify-between text-xs"
                                                                >
                                                                    <span
                                                                        className="truncate text-muted-foreground"
                                                                        title={
                                                                            c.criterion_name
                                                                        }
                                                                    >
                                                                        {
                                                                            c.criterion_name
                                                                        }
                                                                    </span>
                                                                    <span
                                                                        className={`ml-1 shrink-0 font-medium ${gradeColor(c.grade)}`}
                                                                    >
                                                                        {formatDecimal(
                                                                            c.grade,
                                                                        )}
                                                                    </span>
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                </div>
                            ),
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

// ── Tab 3: Por Resultado ──────────────────────────────────────────────────────

function ByOutcomeTab({
    byOutcome,
    byStudent,
}: {
    byOutcome: OutcomeStats[];
    byStudent: StudentStats[];
}) {
    const [selectedId, setSelectedId] = useState<string>(
        byOutcome[0] ? String(byOutcome[0].outcome_id) : '',
    );

    const outcome = byOutcome.find((o) => String(o.outcome_id) === selectedId);

    const typeGroups = byOutcome.reduce<Record<string, OutcomeStats[]>>(
        (acc, o) => {
            const t = o.type_name ?? 'Otros';
            if (!acc[t]) acc[t] = [];
            acc[t].push(o);
            return acc;
        },
        {},
    );

    // Overview bar — one bar per RA, in real grade scale
    const overviewData = byOutcome.map((o) => ({
        name: o.outcome_code ?? `RA${o.outcome_id}`,
        fullName: o.outcome_desc,
        nota: o.group_average,
        type: o.type_name ?? '',
        id: String(o.outcome_id),
    }));

    // Students sorted for selected outcome — include per-criterion breakdown
    const [expandedStudent, setExpandedStudent] = useState<number | null>(null);
    const [studentPage, setStudentPage] = useState(0);
    const STUDENTS_PER_PAGE = 5;

    const studentsForOutcome = outcome
        ? byStudent
              .map((s) => {
                  const oBo = s.by_outcome.find(
                      (o) => String(o.outcome_id) === selectedId,
                  );
                  return {
                      name: s.student_name,
                      enrollment_id: s.enrollment_id,
                      grade: oBo?.grade ?? 0,
                      by_criterion: oBo?.by_criterion ?? [],
                  };
              })
              .sort((a, b) => b.grade - a.grade)
        : [];

    const totalStudentPages = Math.ceil(
        studentsForOutcome.length / STUDENTS_PER_PAGE,
    );
    const pagedStudents = studentsForOutcome.slice(
        studentPage * STUDENTS_PER_PAGE,
        (studentPage + 1) * STUDENTS_PER_PAGE,
    );

    return (
        <div className="space-y-6">
            {/* Overview bars */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Promedio del grupo por resultado de aprendizaje
                    </CardTitle>
                    <p className="text-xs text-muted-foreground">
                        Promedio de las notas individuales de cada estudiante en
                        ese RA · escala 1.3–5.0
                    </p>
                    <div className="flex flex-wrap gap-3">
                        {Object.keys(typeGroups).map((t) => (
                            <span
                                key={t}
                                className="flex items-center gap-1 text-xs"
                            >
                                <span
                                    className="inline-block h-2 w-2 rounded-full"
                                    style={{
                                        backgroundColor:
                                            TYPE_COLORS[t] ?? TYPE_FALLBACK,
                                    }}
                                />
                                {t}
                            </span>
                        ))}
                    </div>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={220}>
                        <BarChart data={overviewData}>
                            <CartesianGrid
                                strokeDasharray="3 3"
                                vertical={false}
                            />
                            <XAxis dataKey="name" tick={{ fontSize: 11 }} />
                            <YAxis
                                domain={[1, 5]}
                                tickCount={5}
                                tick={{ fontSize: 10 }}
                            />
                            <Tooltip
                                formatter={(v) => [
                                    `${formatDecimal(Number(v))} — ${levelLabel(Number(v))}`,
                                    'Promedio del grupo',
                                ]}
                                labelFormatter={(_, payload) =>
                                    (
                                        payload[0]?.payload as {
                                            fullName?: string;
                                        }
                                    )?.fullName ?? ''
                                }
                            />
                            <Bar dataKey="nota" radius={[4, 4, 0, 0]}>
                                {overviewData.map((entry, i) => (
                                    <Cell
                                        key={i}
                                        fill={
                                            TYPE_COLORS[entry.type] ??
                                            TYPE_FALLBACK
                                        }
                                        opacity={
                                            entry.id === selectedId ? 1 : 0.55
                                        }
                                        style={{ cursor: 'pointer' }}
                                        onClick={() => {
                                            setSelectedId(entry.id);
                                            setStudentPage(0);
                                            setExpandedStudent(null);
                                        }}
                                    />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </CardContent>
            </Card>

            {/* Selector por tipo */}
            <div className="flex flex-wrap gap-4">
                {Object.entries(typeGroups).map(([typeName, outcomes]) => (
                    <div key={typeName} className="space-y-1">
                        <p
                            className="text-xs font-semibold tracking-wide uppercase"
                            style={{
                                color: TYPE_COLORS[typeName] ?? TYPE_FALLBACK,
                            }}
                        >
                            {typeName}
                        </p>
                        <div className="flex flex-wrap gap-1">
                            {outcomes.map((o) => (
                                <button
                                    key={o.outcome_id}
                                    type="button"
                                    onClick={() => {
                                        setSelectedId(String(o.outcome_id));
                                        setStudentPage(0);
                                        setExpandedStudent(null);
                                    }}
                                    className={`rounded-full px-3 py-1 text-xs font-medium transition-colors ${String(o.outcome_id) === selectedId ? 'text-white' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                                    style={
                                        String(o.outcome_id) === selectedId
                                            ? {
                                                  backgroundColor:
                                                      TYPE_COLORS[typeName] ??
                                                      TYPE_FALLBACK,
                                              }
                                            : {}
                                    }
                                >
                                    {o.outcome_code ?? `RA${o.outcome_id}`}
                                </button>
                            ))}
                        </div>
                    </div>
                ))}
            </div>

            {outcome && (
                <>
                    {/* Descripción del RA */}
                    <Card
                        className="border-l-4"
                        style={{
                            borderLeftColor:
                                TYPE_COLORS[outcome.type_name ?? ''] ??
                                TYPE_FALLBACK,
                        }}
                    >
                        <CardContent className="pt-4">
                            <div className="flex items-start gap-3">
                                <span
                                    className="shrink-0 rounded-full px-2 py-0.5 text-xs font-bold text-white"
                                    style={{
                                        backgroundColor:
                                            TYPE_COLORS[
                                                outcome.type_name ?? ''
                                            ] ?? TYPE_FALLBACK,
                                    }}
                                >
                                    {outcome.type_name}
                                </span>
                                <p className="text-sm leading-relaxed">
                                    {outcome.outcome_desc}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Métricas + distribuciones + ranking */}
                    <div className="grid gap-4 sm:grid-cols-3">
                        {[
                            {
                                label: 'Promedio del grupo',
                                value: outcome.group_average,
                            },
                            { label: 'Nota más alta', value: outcome.highest },
                            { label: 'Nota más baja', value: outcome.lowest },
                        ].map((m) => (
                            <Card key={m.label}>
                                <CardContent className="pt-4 text-center">
                                    <p
                                        className={`text-3xl font-bold ${gradeColor(Number(m.value))}`}
                                    >
                                        {formatDecimal(Number(m.value))}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {m.label}
                                    </p>
                                    <p
                                        className="text-xs font-medium"
                                        style={{
                                            color: levelColor(levelLabel(Number(m.value))),
                                        }}
                                    >
                                        {levelLabel(Number(m.value))}
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    <DistributionCharts
                        distribution={outcome.distribution}
                        title1={`Calificaciones por nivel en ${outcome.outcome_code ?? `RA${outcome.outcome_id}`}`}
                        title2="Estudiantes que recibieron cada nivel"
                    />

                    {/* Ranking de estudiantes en este RA — paginado + desplegable */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">
                                Nota de cada estudiante en este RA
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                Promedio de sus criterios · clic para ver el
                                detalle por criterio
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-1">
                                {pagedStudents.map((s, i) => {
                                    const globalIdx =
                                        studentPage * STUDENTS_PER_PAGE + i;
                                    const isExpanded =
                                        expandedStudent === s.enrollment_id;
                                    return (
                                        <div
                                            key={s.enrollment_id}
                                            className="overflow-hidden rounded-lg border"
                                        >
                                            <button
                                                type="button"
                                                className="flex w-full items-center gap-3 px-3 py-2 text-sm transition-colors hover:bg-muted/50"
                                                onClick={() =>
                                                    setExpandedStudent(
                                                        isExpanded
                                                            ? null
                                                            : s.enrollment_id,
                                                    )
                                                }
                                            >
                                                <span className="w-5 text-center text-xs text-muted-foreground">
                                                    {globalIdx + 1}
                                                </span>
                                                <span className="flex-1 truncate text-left font-medium">
                                                    {s.name}
                                                </span>
                                                <div className="h-1.5 w-24 shrink-0 overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full rounded-full"
                                                        style={{
                                                            width: `${((s.grade - 1.3) / 3.7) * 100}%`,
                                                            backgroundColor:
                                                                levelColor(levelLabel(
                                                                        s.grade,
                                                                    )) ?? '#ccc',
                                                        }}
                                                    />
                                                </div>
                                                <span
                                                    className={`w-10 shrink-0 text-right text-xs font-bold ${gradeColor(s.grade)}`}
                                                >
                                                    {formatDecimal(s.grade)}
                                                </span>
                                                {isExpanded ? (
                                                    <ChevronUp className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                                ) : (
                                                    <ChevronDown className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                                )}
                                            </button>
                                            {isExpanded && (
                                                <div className="border-t bg-muted/20 px-4 pt-2 pb-3">
                                                    <p className="mb-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                        Calificación por
                                                        criterio
                                                    </p>
                                                    <div className="grid gap-1 sm:grid-cols-2">
                                                        {s.by_criterion.map(
                                                            (c) => (
                                                                <div
                                                                    key={
                                                                        c.criterion_id
                                                                    }
                                                                    className="flex items-center justify-between rounded bg-background px-2 py-1 text-xs"
                                                                >
                                                                    <span
                                                                        className="max-w-40 truncate text-muted-foreground"
                                                                        title={
                                                                            c.criterion_name
                                                                        }
                                                                    >
                                                                        {
                                                                            c.criterion_name
                                                                        }
                                                                    </span>
                                                                    <div className="ml-2 flex shrink-0 items-center gap-1.5">
                                                                        <span
                                                                            className={`rounded px-1.5 py-0.5 text-xs font-semibold ${gradeBgClass(c.grade)}`}
                                                                        >
                                                                            {formatDecimal(
                                                                                c.grade,
                                                                            )}
                                                                        </span>
                                                                        <span className="text-muted-foreground">
                                                                            {
                                                                                c.level_name
                                                                            }
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                            {/* Paginación */}
                            {totalStudentPages > 1 && (
                                <div className="mt-3 flex items-center justify-between">
                                    <p className="text-xs text-muted-foreground">
                                        {studentPage * STUDENTS_PER_PAGE + 1}–
                                        {Math.min(
                                            (studentPage + 1) *
                                                STUDENTS_PER_PAGE,
                                            studentsForOutcome.length,
                                        )}{' '}
                                        de {studentsForOutcome.length}{' '}
                                        estudiantes
                                    </p>
                                    <div className="flex gap-1">
                                        <button
                                            type="button"
                                            disabled={studentPage === 0}
                                            onClick={() => {
                                                setStudentPage((p) => p - 1);
                                                setExpandedStudent(null);
                                            }}
                                            className="rounded border px-2 py-1 text-xs transition-colors hover:bg-muted disabled:opacity-40"
                                        >
                                            ← Anterior
                                        </button>
                                        {Array.from(
                                            { length: totalStudentPages },
                                            (_, i) => (
                                                <button
                                                    key={i}
                                                    type="button"
                                                    onClick={() => {
                                                        setStudentPage(i);
                                                        setExpandedStudent(
                                                            null,
                                                        );
                                                    }}
                                                    className={`rounded border px-2 py-1 text-xs transition-colors ${i === studentPage ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                                >
                                                    {i + 1}
                                                </button>
                                            ),
                                        )}
                                        <button
                                            type="button"
                                            disabled={
                                                studentPage ===
                                                totalStudentPages - 1
                                            }
                                            onClick={() => {
                                                setStudentPage((p) => p + 1);
                                                setExpandedStudent(null);
                                            }}
                                            className="rounded border px-2 py-1 text-xs transition-colors hover:bg-muted disabled:opacity-40"
                                        >
                                            Siguiente →
                                        </button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </>
            )}
        </div>
    );
}

// ── Tab 4: Por Criterio ───────────────────────────────────────────────────────

function ByCriterionTab({ byCriterion }: { byCriterion: CriterionStats[] }) {
    const [expandedCriterion, setExpandedCriterion] = useState<number | null>(
        null,
    );
    const [expandedOutcome, setExpandedOutcome] = useState<number | null>(null);

    const byType = byCriterion.reduce<Record<string, CriterionStats[]>>(
        (acc, c) => {
            const t = c.type_name ?? 'Otros';
            if (!acc[t]) acc[t] = [];
            acc[t].push(c);
            return acc;
        },
        {},
    );

    const minCriterion = [...byCriterion].sort(
        (a, b) => a.group_average - b.group_average,
    )[0];

    return (
        <div className="space-y-6">
            {/* Gráfica global */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Promedio del grupo por criterio de evaluación
                    </CardTitle>
                    <p className="text-xs text-muted-foreground">
                        Promedio de <strong>todas</strong> las calificaciones de
                        ese criterio (todos los RAs que lo usan × todos los
                        estudiantes) · escala 1.3–5.0
                    </p>
                    <div className="flex flex-wrap gap-3">
                        {Object.keys(byType).map((t) => (
                            <span
                                key={t}
                                className="flex items-center gap-1 text-xs"
                            >
                                <span
                                    className="inline-block h-2 w-2 rounded-full"
                                    style={{
                                        backgroundColor:
                                            TYPE_COLORS[t] ?? TYPE_FALLBACK,
                                    }}
                                />
                                {t}
                            </span>
                        ))}
                    </div>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={260}>
                        <BarChart
                            data={byCriterion.map((c) => ({
                                name: c.criterion_name,
                                nota: c.group_average,
                                type: c.type_name ?? '',
                            }))}
                            margin={{ bottom: 80 }}
                        >
                            <CartesianGrid
                                strokeDasharray="3 3"
                                vertical={false}
                            />
                            <XAxis
                                dataKey="name"
                                tick={{ fontSize: 10 }}
                                angle={-40}
                                textAnchor="end"
                                interval={0}
                            />
                            <YAxis
                                domain={[1, 5]}
                                tickCount={5}
                                tick={{ fontSize: 10 }}
                            />
                            <Tooltip
                                formatter={(v, _, p) => [
                                    `${formatDecimal(Number(v))} — ${levelLabel(Number(v))}`,
                                    (p.payload as { type?: string }).type ??
                                        'Criterio',
                                ]}
                            />
                            <Bar dataKey="nota" radius={[4, 4, 0, 0]}>
                                {byCriterion.map((c, i) => (
                                    <Cell
                                        key={i}
                                        fill={
                                            TYPE_COLORS[c.type_name ?? ''] ??
                                            TYPE_FALLBACK
                                        }
                                        opacity={
                                            c.criterion_id ===
                                            minCriterion?.criterion_id
                                                ? 1
                                                : 0.75
                                        }
                                    />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </CardContent>
            </Card>

            {/* Vista jerárquica por tipo → criterio → RAs → estudiantes */}
            {Object.entries(byType).map(([typeName, criteria]) => {
                const typeMin = [...criteria].sort(
                    (a, b) => a.group_average - b.group_average,
                )[0];
                const typeColor = TYPE_COLORS[typeName] ?? TYPE_FALLBACK;

                return (
                    <div key={typeName}>
                        <div className="mb-2 flex items-center gap-2">
                            <span
                                className="inline-block h-3 w-3 rounded-full"
                                style={{ backgroundColor: typeColor }}
                            />
                            <h3
                                className="text-sm font-semibold"
                                style={{ color: typeColor }}
                            >
                                {typeName}
                            </h3>
                            <span className="text-xs text-muted-foreground">
                                — {criteria.length} criterio
                                {criteria.length !== 1 ? 's' : ''}
                            </span>
                        </div>

                        <div className="space-y-2">
                            {criteria.map((c) => {
                                const isMin =
                                    c.criterion_id === typeMin?.criterion_id;
                                const isExpanded =
                                    expandedCriterion === c.criterion_id;

                                return (
                                    <div
                                        key={c.criterion_id}
                                        className={`overflow-hidden rounded-lg border ${isMin ? 'border-amber-300 dark:border-amber-700' : ''}`}
                                    >
                                        {/* Fila del criterio — desplegable */}
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setExpandedCriterion(
                                                    isExpanded
                                                        ? null
                                                        : c.criterion_id,
                                                );
                                                setExpandedOutcome(null);
                                            }}
                                            className={`flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/50 ${isMin ? 'bg-amber-50 dark:bg-amber-950/20' : 'bg-muted/20'}`}
                                        >
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <span
                                                        className={`text-sm font-semibold ${isMin ? 'text-amber-800 dark:text-amber-300' : ''}`}
                                                    >
                                                        {c.criterion_name}
                                                    </span>
                                                    {isMin && (
                                                        <span className="text-xs font-medium text-amber-500">
                                                            ↓ menor del grupo
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    Promedio en{' '}
                                                    {c.by_outcome.length} RA
                                                    {c.by_outcome.length !== 1
                                                        ? 's'
                                                        : ''}{' '}
                                                    ·{' '}
                                                    {c.by_outcome.reduce(
                                                        (s, o) =>
                                                            s +
                                                            o.students.length,
                                                        0,
                                                    )}{' '}
                                                    calificaciones totales
                                                </p>
                                            </div>
                                            <div className="flex shrink-0 items-center gap-3">
                                                <div className="h-1.5 w-24 overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full rounded-full"
                                                        style={{
                                                            width: `${((c.group_average - 1.3) / 3.7) * 100}%`,
                                                            backgroundColor:
                                                                typeColor,
                                                        }}
                                                    />
                                                </div>
                                                <span
                                                    className={`w-10 text-right text-sm font-bold ${gradeColor(c.group_average)}`}
                                                >
                                                    {formatDecimal(
                                                        c.group_average,
                                                    )}
                                                </span>
                                                {isExpanded ? (
                                                    <ChevronUp className="h-3.5 w-3.5 text-muted-foreground" />
                                                ) : (
                                                    <ChevronDown className="h-3.5 w-3.5 text-muted-foreground" />
                                                )}
                                            </div>
                                        </button>

                                        {/* RAs que usan este criterio */}
                                        {isExpanded && (
                                            <div className="divide-y border-t bg-background">
                                                {c.by_outcome.map((o) => {
                                                    const isOutcomeExpanded =
                                                        expandedOutcome ===
                                                        o.outcome_id;
                                                    return (
                                                        <div key={o.outcome_id}>
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    setExpandedOutcome(
                                                                        isOutcomeExpanded
                                                                            ? null
                                                                            : o.outcome_id,
                                                                    )
                                                                }
                                                                className="flex w-full items-center gap-3 px-6 py-2.5 text-left transition-colors hover:bg-muted/30"
                                                            >
                                                                <div className="min-w-0 flex-1">
                                                                    <div className="flex items-center gap-2">
                                                                        <span
                                                                            className="rounded bg-muted px-1.5 py-0.5 text-xs font-semibold"
                                                                            style={{
                                                                                color: typeColor,
                                                                            }}
                                                                        >
                                                                            {o.outcome_code ??
                                                                                `RA${o.outcome_id}`}
                                                                        </span>
                                                                        <span
                                                                            className="max-w-62.5 truncate text-xs text-muted-foreground"
                                                                            title={
                                                                                o.outcome_desc
                                                                            }
                                                                        >
                                                                            {
                                                                                o.outcome_desc
                                                                            }
                                                                        </span>
                                                                    </div>
                                                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                                                        {
                                                                            o
                                                                                .students
                                                                                .length
                                                                        }{' '}
                                                                        estudiante
                                                                        {o
                                                                            .students
                                                                            .length !==
                                                                        1
                                                                            ? 's'
                                                                            : ''}
                                                                    </p>
                                                                </div>
                                                                <div className="flex shrink-0 items-center gap-2">
                                                                    <span
                                                                        className={`text-xs font-bold ${gradeColor(o.group_average)}`}
                                                                    >
                                                                        {formatDecimal(
                                                                            o.group_average,
                                                                        )}
                                                                    </span>
                                                                    {isOutcomeExpanded ? (
                                                                        <ChevronUp className="h-3.5 w-3.5 text-muted-foreground" />
                                                                    ) : (
                                                                        <ChevronDown className="h-3.5 w-3.5 text-muted-foreground" />
                                                                    )}
                                                                </div>
                                                            </button>

                                                            {/* Estudiantes en este RA con este criterio */}
                                                            {isOutcomeExpanded && (
                                                                <div className="border-t bg-muted/10 px-8 py-3">
                                                                    <div className="grid gap-1 sm:grid-cols-2 lg:grid-cols-3">
                                                                        {o.students.map(
                                                                            (
                                                                                s,
                                                                                si,
                                                                            ) => (
                                                                                <div
                                                                                    key={
                                                                                        si
                                                                                    }
                                                                                    className="flex items-center gap-2 rounded border bg-background px-2 py-1.5 text-xs"
                                                                                >
                                                                                    <span
                                                                                        className="flex-1 truncate font-medium"
                                                                                        title={
                                                                                            s.student_name
                                                                                        }
                                                                                    >
                                                                                        {
                                                                                            s.student_name
                                                                                        }
                                                                                    </span>
                                                                                    <span className="shrink-0 text-muted-foreground">
                                                                                        {
                                                                                            s.level_name
                                                                                        }
                                                                                    </span>
                                                                                    <span
                                                                                        className={`shrink-0 rounded px-1.5 py-0.5 font-semibold ${gradeBgClass(s.grade)}`}
                                                                                    >
                                                                                        {formatDecimal(
                                                                                            s.grade,
                                                                                        )}
                                                                                    </span>
                                                                                </div>
                                                                            ),
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            )}
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                );
            })}

            {minCriterion && (
                <Card className="border-amber-200 bg-amber-50/40 dark:border-amber-800 dark:bg-amber-950/10">
                    <CardContent className="pt-5">
                        <div className="flex items-start gap-4">
                            <Zap className="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
                            <div>
                                <p className="text-xs font-semibold tracking-wide text-amber-700 uppercase dark:text-amber-400">
                                    Criterio con menor promedio en el grupo
                                </p>
                                <p className="mt-0.5 text-lg font-bold text-amber-800 dark:text-amber-300">
                                    {minCriterion.criterion_name}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Tipo: {minCriterion.type_name} · Promedio:{' '}
                                    <strong
                                        className={gradeColor(
                                            minCriterion.group_average,
                                        )}
                                    >
                                        {formatDecimal(
                                            minCriterion.group_average,
                                        )}
                                    </strong>{' '}
                                    ({levelLabel(minCriterion.group_average)}) ·{' '}
                                    aparece en {minCriterion.by_outcome.length}{' '}
                                    resultado
                                    {minCriterion.by_outcome.length !== 1
                                        ? 's'
                                        : ''}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function StatisticsShow({ programming, statistics }: Props) {
    // Set before the tree renders, so the nested components that label grades
    // read the scale the backend sent rather than a stale one.
    setActiveScale(statistics.scale);

    const [activeTab, setActiveTab] = useState('summary');
    const [highlightedEnrollmentId, setHighlightedEnrollmentId] = useState<
        number | undefined
    >();

    function handleStudentClick(id: number) {
        setHighlightedEnrollmentId(id);
        setActiveTab('by-student');
    }

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/professor/dashboard' },
        {
            title: programming.academic_space?.name ?? 'Programación',
            href: GradingController.show.url(programming),
        },
        {
            title: 'Estadísticas',
            href: StatisticsController.show.url(programming),
        },
    ];

    return (
        <ProfessorLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Estadísticas — ${programming.academic_space?.name ?? ''}`}
            />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title={`Estadísticas: ${programming.academic_space?.name ?? ''}`}
                    description={`${programming.academic_period?.name ?? ''}${programming.group ? ` · Grupo ${programming.group}` : ''} · ${programming.modality?.name ?? ''}`}
                >
                    <Button variant="outline" asChild>
                        <a
                            href={GradingController.downloadReport.url(
                                programming,
                            )}
                            download
                        >
                            ↓ Exportar reporte Excel
                        </a>
                    </Button>
                </PageHeader>

                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList className="mb-2">
                        <TabsTrigger value="summary">
                            Resumen general
                        </TabsTrigger>
                        <TabsTrigger value="by-student">
                            Por estudiante
                        </TabsTrigger>
                        <TabsTrigger value="by-outcome">
                            Por resultado
                        </TabsTrigger>
                        <TabsTrigger value="by-criterion">
                            Por criterio
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="summary" className="mt-4">
                        <SummaryTab
                            summary={statistics.summary}
                            byStudent={statistics.byStudent}
                            onStudentClick={handleStudentClick}
                        />
                    </TabsContent>

                    <TabsContent value="by-student" className="mt-4">
                        <ByStudentTab
                            byStudent={statistics.byStudent}
                            byOutcome={statistics.byOutcome}
                            initialEnrollmentId={highlightedEnrollmentId}
                        />
                    </TabsContent>

                    <TabsContent value="by-outcome" className="mt-4">
                        <ByOutcomeTab
                            byOutcome={statistics.byOutcome}
                            byStudent={statistics.byStudent}
                        />
                    </TabsContent>

                    <TabsContent value="by-criterion" className="mt-4">
                        <ByCriterionTab byCriterion={statistics.byCriterion} />
                    </TabsContent>
                </Tabs>
            </div>
        </ProfessorLayout>
    );
}
