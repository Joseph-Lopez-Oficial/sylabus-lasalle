import { Head, Link } from '@inertiajs/react';
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
import * as ProgrammingController from '@/actions/App/Http/Controllers/Admin/ProgrammingController';
import { DownloadButton } from '@/components/download-button';
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
import AdminLayout from '@/layouts/admin/admin-layout';
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
    academic_space?: { id: number; name: string; code: string };
    professor?: { id: number; first_name: string; last_name: string };
    modality?: { id: number; name: string };
};

type Props = {
    programming: ProgrammingInfo;
    statistics: ProgrammingStats;
    completeness: { percentage: number; total: number; completed: number };
};

// ── Constants (same as professor stats) ───────────────────────────────────────

const LEVEL_COLOR_LIST = ['#ef4444', '#f97316', '#22c55e', '#3b82f6'];

const TYPE_COLORS: Record<string, string> = {
    Conocimiento: '#6366f1',
    Habilidad: '#10b981',
    Actitud: '#f59e0b',
};
const TYPE_FALLBACK = '#8b5cf6';

const gradeColor = gradeTextClass;
const gradeBgClass = gradeBadgeClass;
const levelLabel = levelLabelForGrade;

// ── Shared distribution charts ────────────────────────────────────────────────

function DistributionCharts({
    distribution,
    title1,
    title2,
}: {
    distribution: LevelDistribution[];
    title1?: string;
    title2?: string;
}) {
    const data = distribution.map((d) => ({
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
                    <CardTitle className="text-sm">
                        {title1 ?? 'Calificaciones individuales por nivel'}
                    </CardTitle>
                    <p className="text-xs text-muted-foreground">
                        Total de asignaciones criterio × RA × estudiante
                    </p>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={180}>
                        <BarChart data={data} layout="vertical">
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
                                {data.map((d, i) => (
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
                    <CardTitle className="text-sm">
                        {title2 ?? 'Estudiantes que recibieron cada nivel'}
                    </CardTitle>
                    <p className="text-xs text-muted-foreground">
                        Cuántos estudiantes distintos recibieron cada nivel
                    </p>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={180}>
                        <BarChart data={data} layout="vertical">
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
                                    `${v} est. (${(p.payload as { pctEst?: number }).pctEst ?? 0}%)`,
                                    'Estudiantes',
                                ]}
                            />
                            <Bar dataKey="estudiantes" radius={[0, 4, 4, 0]}>
                                {data.map((d, i) => (
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

// ── Tab 1: Resumen ────────────────────────────────────────────────────────────

function SummaryTab({
    summary,
    byStudent,
    onStudentClick,
}: {
    summary: ProgrammingStats['summary'];
    byStudent: StudentStats[];
    onStudentClick: (id: number) => void;
}) {
    const g = summary.overall_average;
    const highCount = byStudent.filter((s) => s.final_average >= 3.8).length;
    const belowCount = byStudent.filter((s) => s.final_average < 2.5).length;

    return (
        <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {[
                    {
                        label: 'Promedio del grupo',
                        value: formatDecimal(g),
                        sub: `${levelLabel(g)} · promedio de promedios finales`,
                        color: gradeColor(g),
                    },
                    {
                        label: 'Estudiantes calificados',
                        value: String(byStudent.length),
                        sub: 'con nota final calculada',
                        color: 'text-foreground',
                    },
                    {
                        label: 'Competente o Destacado',
                        value: String(highCount),
                        sub: 'estudiantes · nota ≥ 3.8',
                        color: 'text-blue-600',
                    },
                    {
                        label: 'Bajo nivel Básico',
                        value: String(belowCount),
                        sub: 'estudiantes · nota < 2.5',
                        color:
                            belowCount > 0 ? 'text-red-600' : 'text-green-600',
                    },
                ].map((k) => (
                    <Card key={k.label}>
                        <CardContent className="pt-5 text-center">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                {k.label}
                            </p>
                            <p className={`mt-1 text-4xl font-bold ${k.color}`}>
                                {k.value}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {k.sub}
                            </p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <DistributionCharts distribution={summary.distribution} />

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
                                Todos en nivel Básico o superior
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </div>
    );
}

// ── Tab 2: Por estudiante ─────────────────────────────────────────────────────

function ByStudentTab({
    byStudent,
    initialId,
}: {
    byStudent: StudentStats[];
    initialId?: number;
}) {
    const sorted = [...byStudent].sort(
        (a, b) => b.final_average - a.final_average,
    );
    const [selectedId, setSelectedId] = useState<string>(
        initialId
            ? String(initialId)
            : sorted[0]
              ? String(sorted[0].enrollment_id)
              : '',
    );
    const student = byStudent.find(
        (s) => String(s.enrollment_id) === selectedId,
    );
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
    const raBarData =
        student?.by_outcome.map((o) => ({
            name: o.outcome_code ?? `RA${o.outcome_id}`,
            fullName: o.outcome_desc,
            nota: o.grade,
            type: o.type_name ?? '',
        })) ?? [];

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-3">
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
                                            className="inline-block h-2 w-2 rounded-full"
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
                                {Object.entries(outcomesByType).map(
                                    ([typeName, outcomes]) => {
                                        const avg =
                                            outcomes.reduce(
                                                (s, o) => s + o.grade,
                                                0,
                                            ) / outcomes.length;
                                        return (
                                            <div
                                                key={typeName}
                                                className="rounded-lg border p-3"
                                            >
                                                <div className="mb-0.5 flex items-center justify-between">
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
                                                        className={`text-sm font-bold ${gradeColor(avg)}`}
                                                    >
                                                        {formatDecimal(avg)}
                                                    </span>
                                                </div>
                                                <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full rounded-full"
                                                        style={{
                                                            width: `${((avg - 1.3) / 3.7) * 100}%`,
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
                                                    {outcomes.length} RAs de
                                                    tipo {typeName} · cada RA es
                                                    el promedio de sus{' '}
                                                    {outcomes[0]?.by_criterion
                                                        .length ?? '—'}{' '}
                                                    criterios
                                                </p>
                                            </div>
                                        );
                                    },
                                )}
                            </CardContent>
                        </Card>

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
                                    <BarChart data={raBarData}>
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
                                            labelFormatter={(_, p) =>
                                                (
                                                    p[0]?.payload as {
                                                        fullName?: string;
                                                    }
                                                )?.fullName ?? ''
                                            }
                                        />
                                        <Bar
                                            dataKey="nota"
                                            radius={[4, 4, 0, 0]}
                                        >
                                            {raBarData.map((e, i) => (
                                                <Cell
                                                    key={i}
                                                    fill={
                                                        TYPE_COLORS[e.type] ??
                                                        TYPE_FALLBACK
                                                    }
                                                />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    </div>

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
                                        {outcomes.map((o) => (
                                            <Card
                                                key={o.outcome_id}
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
                                                            {o.outcome_code ??
                                                                `RA${o.outcome_id}`}
                                                        </p>
                                                        <span
                                                            className={`shrink-0 rounded px-1.5 py-0.5 text-xs font-bold ${gradeBgClass(o.grade)}`}
                                                        >
                                                            {formatDecimal(
                                                                o.grade,
                                                            )}
                                                        </span>
                                                    </div>
                                                    <p
                                                        className="mb-2 line-clamp-2 text-xs text-muted-foreground"
                                                        title={o.outcome_desc}
                                                    >
                                                        {o.outcome_desc}
                                                    </p>
                                                    <div className="space-y-1">
                                                        {o.by_criterion.map(
                                                            (c) => (
                                                                <div
                                                                    key={
                                                                        c.criterion_id
                                                                    }
                                                                    className="flex items-center justify-between text-xs"
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

// ── Tab 3: Por resultado ──────────────────────────────────────────────────────

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
    const [expandedStudent, setExpandedStudent] = useState<number | null>(null);
    const [studentPage, setStudentPage] = useState(0);
    const PER_PAGE = 5;

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
    const overviewData = byOutcome.map((o) => ({
        name: o.outcome_code ?? `RA${o.outcome_id}`,
        fullName: o.outcome_desc,
        nota: o.group_average,
        type: o.type_name ?? '',
        id: String(o.outcome_id),
    }));

    const studentsForOutcome = outcome
        ? byStudent
              .map((s) => {
                  const ob = s.by_outcome.find(
                      (o) => String(o.outcome_id) === selectedId,
                  );
                  return {
                      name: s.student_name,
                      enrollment_id: s.enrollment_id,
                      grade: ob?.grade ?? 0,
                      by_criterion: ob?.by_criterion ?? [],
                  };
              })
              .sort((a, b) => b.grade - a.grade)
        : [];
    const totalPages = Math.ceil(studentsForOutcome.length / PER_PAGE);
    const paged = studentsForOutcome.slice(
        studentPage * PER_PAGE,
        (studentPage + 1) * PER_PAGE,
    );

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Promedio del grupo por resultado de aprendizaje
                    </CardTitle>
                    <p className="text-xs text-muted-foreground">
                        Promedio de las notas de cada estudiante en ese RA ·
                        escala 1.3–5.0
                    </p>
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
                                labelFormatter={(_, p) =>
                                    (p[0]?.payload as { fullName?: string })
                                        ?.fullName ?? ''
                                }
                            />
                            <Bar dataKey="nota" radius={[4, 4, 0, 0]}>
                                {overviewData.map((e, i) => (
                                    <Cell
                                        key={i}
                                        fill={
                                            TYPE_COLORS[e.type] ?? TYPE_FALLBACK
                                        }
                                        opacity={e.id === selectedId ? 1 : 0.55}
                                        style={{ cursor: 'pointer' }}
                                        onClick={() => {
                                            setSelectedId(e.id);
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
                                {paged.map((s, i) => {
                                    const gi = studentPage * PER_PAGE + i;
                                    const exp =
                                        expandedStudent === s.enrollment_id;
                                    return (
                                        <div
                                            key={s.enrollment_id}
                                            className="overflow-hidden rounded-lg border"
                                        >
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setExpandedStudent(
                                                        exp
                                                            ? null
                                                            : s.enrollment_id,
                                                    )
                                                }
                                                className="flex w-full items-center gap-3 px-3 py-2 text-sm transition-colors hover:bg-muted/50"
                                            >
                                                <span className="w-5 text-center text-xs text-muted-foreground">
                                                    {gi + 1}
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
                                                {exp ? (
                                                    <ChevronUp className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                                ) : (
                                                    <ChevronDown className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                                )}
                                            </button>
                                            {exp && (
                                                <div className="border-t bg-muted/20 px-4 pt-2 pb-3">
                                                    <div className="grid gap-1 sm:grid-cols-2">
                                                        {s.by_criterion.map(
                                                            (c) => (
                                                                <div
                                                                    key={
                                                                        c.criterion_id
                                                                    }
                                                                    className="flex items-center gap-2 rounded border bg-background px-2 py-1.5 text-xs"
                                                                >
                                                                    <span className="flex-1 truncate font-medium">
                                                                        {
                                                                            c.criterion_name
                                                                        }
                                                                    </span>
                                                                    <span className="shrink-0 text-muted-foreground">
                                                                        {
                                                                            c.level_name
                                                                        }
                                                                    </span>
                                                                    <span
                                                                        className={`shrink-0 rounded px-1.5 py-0.5 font-semibold ${gradeBgClass(c.grade)}`}
                                                                    >
                                                                        {formatDecimal(
                                                                            c.grade,
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
                            {totalPages > 1 && (
                                <div className="mt-3 flex items-center justify-between">
                                    <p className="text-xs text-muted-foreground">
                                        {studentPage * PER_PAGE + 1}–
                                        {Math.min(
                                            (studentPage + 1) * PER_PAGE,
                                            studentsForOutcome.length,
                                        )}{' '}
                                        de {studentsForOutcome.length}
                                    </p>
                                    <div className="flex gap-1">
                                        <button
                                            type="button"
                                            disabled={studentPage === 0}
                                            onClick={() => {
                                                setStudentPage((p) => p - 1);
                                                setExpandedStudent(null);
                                            }}
                                            className="rounded border px-2 py-1 text-xs hover:bg-muted disabled:opacity-40"
                                        >
                                            ← Anterior
                                        </button>
                                        {Array.from(
                                            { length: totalPages },
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
                                                    className={`rounded border px-2 py-1 text-xs ${i === studentPage ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                                >
                                                    {i + 1}
                                                </button>
                                            ),
                                        )}
                                        <button
                                            type="button"
                                            disabled={
                                                studentPage === totalPages - 1
                                            }
                                            onClick={() => {
                                                setStudentPage((p) => p + 1);
                                                setExpandedStudent(null);
                                            }}
                                            className="rounded border px-2 py-1 text-xs hover:bg-muted disabled:opacity-40"
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

// ── Tab 4: Por criterio ───────────────────────────────────────────────────────

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
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Promedio del grupo por criterio de evaluación
                    </CardTitle>
                    <p className="text-xs text-muted-foreground">
                        Promedio de todas las calificaciones de ese criterio ·
                        escala 1.3–5.0
                    </p>
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

            {Object.entries(byType).map(([typeName, criteria]) => {
                const typeColor = TYPE_COLORS[typeName] ?? TYPE_FALLBACK;
                const typeMin = [...criteria].sort(
                    (a, b) => a.group_average - b.group_average,
                )[0];
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
                        </div>
                        <div className="space-y-2">
                            {criteria.map((c) => {
                                const isMin =
                                    c.criterion_id === typeMin?.criterion_id;
                                const isExp =
                                    expandedCriterion === c.criterion_id;
                                return (
                                    <div
                                        key={c.criterion_id}
                                        className={`overflow-hidden rounded-lg border ${isMin ? 'border-amber-300 dark:border-amber-700' : ''}`}
                                    >
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setExpandedCriterion(
                                                    isExp
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
                                                    {c.by_outcome.length} RA
                                                    {c.by_outcome.length !== 1
                                                        ? 's'
                                                        : ''}
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
                                                {isExp ? (
                                                    <ChevronUp className="h-3.5 w-3.5 text-muted-foreground" />
                                                ) : (
                                                    <ChevronDown className="h-3.5 w-3.5 text-muted-foreground" />
                                                )}
                                            </div>
                                        </button>
                                        {isExp && (
                                            <div className="divide-y border-t bg-background">
                                                {c.by_outcome.map((o) => {
                                                    const oe =
                                                        expandedOutcome ===
                                                        o.outcome_id;
                                                    return (
                                                        <div key={o.outcome_id}>
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    setExpandedOutcome(
                                                                        oe
                                                                            ? null
                                                                            : o.outcome_id,
                                                                    )
                                                                }
                                                                className="flex w-full items-center gap-3 px-6 py-2.5 text-left transition-colors hover:bg-muted/30"
                                                            >
                                                                <div className="min-w-0 flex-1">
                                                                    <span
                                                                        className="rounded bg-muted px-1.5 py-0.5 text-xs font-semibold"
                                                                        style={{
                                                                            color: typeColor,
                                                                        }}
                                                                    >
                                                                        {o.outcome_code ??
                                                                            `RA${o.outcome_id}`}
                                                                    </span>
                                                                    <span className="ml-2 max-w-62.5 truncate text-xs text-muted-foreground">
                                                                        {
                                                                            o.outcome_desc
                                                                        }
                                                                    </span>
                                                                </div>
                                                                <span
                                                                    className={`text-xs font-bold ${gradeColor(o.group_average)}`}
                                                                >
                                                                    {formatDecimal(
                                                                        o.group_average,
                                                                    )}
                                                                </span>
                                                                {oe ? (
                                                                    <ChevronUp className="h-3.5 w-3.5 text-muted-foreground" />
                                                                ) : (
                                                                    <ChevronDown className="h-3.5 w-3.5 text-muted-foreground" />
                                                                )}
                                                            </button>
                                                            {oe && (
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
                                                                                    <span className="flex-1 truncate font-medium">
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
                                    ({levelLabel(minCriterion.group_average)})
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}

// ── Incomplete state ──────────────────────────────────────────────────────────

function IncompleteBanner({
    completeness,
}: {
    completeness: Props['completeness'];
}) {
    return (
        <Card className="border-amber-200 bg-amber-50/40 dark:border-amber-800 dark:bg-amber-950/10">
            <CardContent className="pt-5">
                <div className="flex items-start gap-3">
                    <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
                    <div className="flex-1">
                        <p className="text-sm font-semibold text-amber-800 dark:text-amber-300">
                            El consolidado aún no está cerrado por el profesor —
                            mostrando datos parciales
                        </p>
                        <div className="mt-3">
                            <div className="mb-1 flex items-center justify-between text-xs">
                                <span className="text-muted-foreground">
                                    Progreso de calificación
                                </span>
                                <span className="font-semibold">
                                    {formatDecimal(completeness.percentage)}%
                                </span>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-amber-400 transition-all"
                                    style={{
                                        width: `${Math.min(completeness.percentage, 100)}%`,
                                    }}
                                />
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {completeness.completed} de {completeness.total}{' '}
                                calificaciones registradas
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function ProgrammingStatistics({
    programming,
    statistics,
    completeness,
}: Props) {
    // Set before the tree renders, so nested components label grades from the
    // configured scale rather than fixed thresholds.
    setActiveScale(statistics.scale);

    const [activeTab, setActiveTab] = useState('summary');
    const [highlightedId, setHighlightedId] = useState<number | undefined>();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Programaciones', href: ProgrammingController.index.url() },
        {
            title: programming.academic_space?.name ?? 'Programación',
            href: ProgrammingController.show.url(programming),
        },
        {
            title: 'Estadísticas',
            href: ProgrammingController.statistics.url(programming),
        },
    ];

    function handleStudentClick(id: number) {
        setHighlightedId(id);
        setActiveTab('by-student');
    }

    const isComplete = completeness.percentage >= 100;

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Estadísticas — ${programming.academic_space?.name ?? ''}`}
            />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title={`Estadísticas: ${programming.academic_space?.name ?? ''}`}
                    description={`${programming.academic_period?.name ?? ''}${programming.group ? ` · Grupo ${programming.group}` : ''} · ${programming.modality?.name ?? ''} · Prof. ${programming.professor?.first_name ?? ''} ${programming.professor?.last_name ?? ''}`}
                >
                    <DownloadButton
                        href={ProgrammingController.downloadStatistics.url(
                            programming,
                        )}
                    >
                        Exportar Excel
                    </DownloadButton>
                    <Button variant="outline" asChild>
                        <Link
                            href={ProgrammingController.show.url(programming)}
                        >
                            ← Volver
                        </Link>
                    </Button>
                </PageHeader>

                {!isComplete && (
                    <IncompleteBanner completeness={completeness} />
                )}

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
                            initialId={highlightedId}
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
        </AdminLayout>
    );
}
