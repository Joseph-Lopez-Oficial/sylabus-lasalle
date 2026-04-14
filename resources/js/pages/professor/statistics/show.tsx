import { Head } from '@inertiajs/react';
import { useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    PolarAngleAxis,
    PolarGrid,
    PolarRadiusAxis,
    Radar,
    RadarChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import * as GradingController from '@/actions/App/Http/Controllers/Professor/GradingController';
import * as StatisticsController from '@/actions/App/Http/Controllers/Professor/StatisticsController';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
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
import { formatDecimal } from '@/lib/utils';
import type { BreadcrumbItem, ProgrammingStats } from '@/types';

// ── Types ─────────────────────────────────────────────────────────────────────

type ProgrammingInfo = {
    id: number;
    period: string;
    group: string | null;
    academic_space: { id: number; name: string; code: string };
    professor: { id: number; first_name: string; last_name: string };
    modality: { id: number; name: string };
};

type Props = {
    programming: ProgrammingInfo;
    statistics: ProgrammingStats;
    completeness: { percentage: number; total: number; completed: number };
};

// ── Color palette ─────────────────────────────────────────────────────────────

const LEVEL_COLORS = ['#ef4444', '#f97316', '#22c55e', '#3b82f6'];
const CRITERION_COLORS = ['#6366f1', '#8b5cf6', '#ec4899', '#14b8a6'];

// ── Summary tab ───────────────────────────────────────────────────────────────

function SummaryTab({ summary }: { summary: ProgrammingStats['summary'] }) {
    const donutData = summary.distribution.map((d) => ({
        name: d.level_name,
        value: d.count,
        percentage: d.percentage,
    }));

    return (
        <div className="grid gap-6 lg:grid-cols-2">
            {/* Promedio general */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Promedio General del Grupo
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-5xl font-bold text-primary">
                        {formatDecimal(summary.overall_average)}
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">
                        sobre 16 puntos posibles
                    </p>
                </CardContent>
            </Card>

            {/* Distribución de niveles — dona */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Distribución de Niveles de Desempeño
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={200}>
                        <PieChart>
                            <Pie
                                data={donutData}
                                cx="50%"
                                cy="50%"
                                innerRadius={55}
                                outerRadius={80}
                                paddingAngle={3}
                                dataKey="value"
                                label={({ name, payload }) =>
                                    `${name}: ${(payload as { percentage?: number }).percentage ?? 0}%`
                                }
                                labelLine={false}
                            >
                                {donutData.map((_, i) => (
                                    <Cell
                                        key={i}
                                        fill={
                                            LEVEL_COLORS[
                                                i % LEVEL_COLORS.length
                                            ]
                                        }
                                    />
                                ))}
                            </Pie>
                            <Tooltip
                                formatter={(value, name) => [
                                    `${value} calificaciones`,
                                    name,
                                ]}
                            />
                        </PieChart>
                    </ResponsiveContainer>
                </CardContent>
            </Card>

            {/* Top 5 estudiantes */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Top 5 Estudiantes
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-2">
                        {summary.top_students.map((s, i) => (
                            <div
                                key={s.enrollment_id}
                                className="flex items-center justify-between"
                            >
                                <div className="flex items-center gap-2">
                                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                                        {i + 1}
                                    </span>
                                    <span className="text-sm">
                                        {s.student_name}
                                    </span>
                                </div>
                                <Badge variant="secondary">
                                    {formatDecimal(s.final_average)}
                                </Badge>
                            </div>
                        ))}
                        {summary.top_students.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Sin datos
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>

            {/* Estudiantes por debajo del nivel básico */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Requieren Atención
                        {summary.below_basic.length > 0 && (
                            <Badge variant="destructive" className="ml-2">
                                {summary.below_basic.length}
                            </Badge>
                        )}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {summary.below_basic.length === 0 ? (
                        <p className="text-sm font-medium text-green-600">
                            ✓ Todos los estudiantes están por encima del nivel
                            Básico
                        </p>
                    ) : (
                        <div className="space-y-2">
                            {summary.below_basic.map((s) => (
                                <div
                                    key={s.enrollment_id}
                                    className="flex items-center justify-between"
                                >
                                    <span className="text-sm">
                                        {s.student_name}
                                    </span>
                                    <Badge variant="destructive">
                                        {formatDecimal(s.final_average)}
                                    </Badge>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

// ── By Student tab ────────────────────────────────────────────────────────────

function ByStudentTab({
    byStudent,
}: {
    byStudent: ProgrammingStats['byStudent'];
    byCriterion: ProgrammingStats['byCriterion'];
}) {
    const [selectedId, setSelectedId] = useState<string>(
        byStudent[0] ? String(byStudent[0].enrollment_id) : '',
    );

    const student = byStudent.find(
        (s) => String(s.enrollment_id) === selectedId,
    );

    const radarData =
        student?.by_criterion.map((c) => ({
            criterion: c.criterion_name,
            promedio: c.average,
            fullMark: 4,
        })) ?? [];

    return (
        <div className="space-y-4">
            <div className="max-w-sm">
                <Select value={selectedId} onValueChange={setSelectedId}>
                    <SelectTrigger>
                        <SelectValue placeholder="Selecciona un estudiante" />
                    </SelectTrigger>
                    <SelectContent>
                        {byStudent.map((s) => (
                            <SelectItem
                                key={s.enrollment_id}
                                value={String(s.enrollment_id)}
                            >
                                {s.student_name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {student && (
                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                {student.student_name}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="mb-4 rounded-lg bg-primary/10 p-4 text-center">
                                <p className="text-xs text-muted-foreground">
                                    Promedio final
                                </p>
                                <p className="text-4xl font-bold text-primary">
                                    {formatDecimal(student.final_average)}
                                </p>
                            </div>
                            <div className="space-y-2">
                                {student.by_criterion.map((c) => (
                                    <div
                                        key={c.criterion_id}
                                        className="flex items-center justify-between text-sm"
                                    >
                                        <span className="text-muted-foreground">
                                            {c.criterion_name}
                                        </span>
                                        <span className="font-medium">
                                            {formatDecimal(c.average)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Perfil por Criterio
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={250}>
                                <RadarChart data={radarData}>
                                    <PolarGrid />
                                    <PolarAngleAxis
                                        dataKey="criterion"
                                        tick={{ fontSize: 11 }}
                                    />
                                    <PolarRadiusAxis
                                        angle={90}
                                        domain={[0, 4]}
                                        tick={{ fontSize: 10 }}
                                    />
                                    <Radar
                                        name="Promedio"
                                        dataKey="promedio"
                                        stroke="#6366f1"
                                        fill="#6366f1"
                                        fillOpacity={0.4}
                                    />
                                    <Tooltip
                                        formatter={(v) =>
                                            formatDecimal(Number(v))
                                        }
                                    />
                                </RadarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>
            )}
        </div>
    );
}

// ── By Outcome tab ────────────────────────────────────────────────────────────

function ByOutcomeTab({
    byOutcome,
    byStudent,
}: {
    byOutcome: ProgrammingStats['byOutcome'];
    byStudent: ProgrammingStats['byStudent'];
}) {
    const [selectedOutcomeId, setSelectedOutcomeId] = useState<string>(
        byOutcome[0] ? String(byOutcome[0].outcome_id) : '',
    );

    const outcome = byOutcome.find(
        (o) => String(o.outcome_id) === selectedOutcomeId,
    );

    const distData =
        outcome?.distribution.map((d) => ({
            name: d.level_name,
            porcentaje: d.percentage,
            count: d.count,
        })) ?? [];

    // Students ordered by total for this outcome
    const studentsForOutcome = byStudent
        .map((s) => ({
            name: s.student_name,
            total:
                s.totals_by_outcome[
                    byOutcome.findIndex(
                        (o) => String(o.outcome_id) === selectedOutcomeId,
                    )
                ] ?? 0,
        }))
        .sort((a, b) => b.total - a.total);

    return (
        <div className="space-y-4">
            <div className="max-w-xl">
                <Select
                    value={selectedOutcomeId}
                    onValueChange={setSelectedOutcomeId}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Selecciona un resultado" />
                    </SelectTrigger>
                    <SelectContent>
                        {byOutcome.map((o) => (
                            <SelectItem
                                key={o.outcome_id}
                                value={String(o.outcome_id)}
                            >
                                <span className="line-clamp-1">
                                    {o.outcome_desc}
                                </span>
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {outcome && (
                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="space-y-4">
                        <div className="grid grid-cols-3 gap-3">
                            {[
                                {
                                    label: 'Promedio grupo',
                                    value: outcome.group_average,
                                },
                                { label: 'Más alto', value: outcome.highest },
                                { label: 'Más bajo', value: outcome.lowest },
                            ].map((m) => (
                                <Card key={m.label}>
                                    <CardContent className="pt-4 text-center">
                                        <p className="text-2xl font-bold">
                                            {formatDecimal(Number(m.value))}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {m.label}
                                        </p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Distribución de Niveles
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={200}>
                                    <BarChart data={distData} layout="vertical">
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            horizontal={false}
                                        />
                                        <XAxis
                                            type="number"
                                            domain={[0, 100]}
                                            unit="%"
                                            tick={{ fontSize: 11 }}
                                        />
                                        <YAxis
                                            type="category"
                                            dataKey="name"
                                            tick={{ fontSize: 11 }}
                                            width={90}
                                        />
                                        <Tooltip
                                            formatter={(v, _, p) => [
                                                `${v}% (${p.payload.count})`,
                                                'Estudiantes',
                                            ]}
                                        />
                                        <Bar
                                            dataKey="porcentaje"
                                            radius={[0, 4, 4, 0]}
                                        >
                                            {distData.map((_, i) => (
                                                <Cell
                                                    key={i}
                                                    fill={
                                                        LEVEL_COLORS[
                                                            i %
                                                                LEVEL_COLORS.length
                                                        ]
                                                    }
                                                />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Estudiantes — Mayor a menor
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-1.5">
                                {studentsForOutcome.map((s, i) => (
                                    <div
                                        key={i}
                                        className={`flex items-center justify-between rounded px-2 py-1 text-sm ${s.total < 8 ? 'bg-red-50 dark:bg-red-950/20' : ''}`}
                                    >
                                        <span
                                            className={
                                                s.total < 8
                                                    ? 'text-red-700 dark:text-red-400'
                                                    : ''
                                            }
                                        >
                                            {s.name}
                                        </span>
                                        <Badge
                                            variant={
                                                s.total < 8
                                                    ? 'destructive'
                                                    : 'secondary'
                                            }
                                        >
                                            {s.total}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}
        </div>
    );
}

// ── By Criterion tab ──────────────────────────────────────────────────────────

function ByCriterionTab({
    byCriterion,
}: {
    byCriterion: ProgrammingStats['byCriterion'];
}) {
    const chartData = byCriterion.map((c, i) => ({
        name: c.criterion_name,
        promedio: c.group_average,
        color: CRITERION_COLORS[i % CRITERION_COLORS.length],
    }));

    const minCriterion = [...byCriterion].sort(
        (a, b) => a.group_average - b.group_average,
    )[0];

    return (
        <div className="grid gap-6 lg:grid-cols-2">
            <Card className="lg:col-span-2">
                <CardHeader>
                    <CardTitle className="text-base">
                        Promedio del Grupo por Criterio de Evaluación
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={250}>
                        <BarChart data={chartData}>
                            <CartesianGrid
                                strokeDasharray="3 3"
                                vertical={false}
                            />
                            <XAxis dataKey="name" tick={{ fontSize: 12 }} />
                            <YAxis domain={[0, 4]} tick={{ fontSize: 11 }} />
                            <Tooltip
                                formatter={(v) => [
                                    formatDecimal(Number(v)),
                                    'Promedio',
                                ]}
                            />
                            <Bar dataKey="promedio" radius={[4, 4, 0, 0]}>
                                {chartData.map((entry, i) => (
                                    <Cell key={i} fill={entry.color} />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Resumen por Criterio
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b">
                                <th className="py-2 text-left font-medium text-muted-foreground">
                                    Criterio
                                </th>
                                <th className="py-2 text-right font-medium text-muted-foreground">
                                    Promedio
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {byCriterion.map((c) => (
                                <tr
                                    key={c.criterion_id}
                                    className={`border-b last:border-0 ${c.criterion_id === minCriterion?.criterion_id ? 'text-amber-700 dark:text-amber-400' : ''}`}
                                >
                                    <td className="py-2">
                                        {c.criterion_name}
                                        {c.criterion_id ===
                                            minCriterion?.criterion_id && (
                                            <Badge
                                                variant="outline"
                                                className="ml-2 border-amber-300 text-xs text-amber-700"
                                            >
                                                menor
                                            </Badge>
                                        )}
                                    </td>
                                    <td className="py-2 text-right font-medium">
                                        {formatDecimal(c.group_average)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            {minCriterion && (
                <Card className="border-amber-200 bg-amber-50/50 dark:border-amber-800 dark:bg-amber-950/10">
                    <CardContent className="pt-6">
                        <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                            Criterio con menor promedio
                        </p>
                        <p className="mt-1 text-xl font-bold text-amber-700 dark:text-amber-300">
                            {minCriterion.criterion_name}
                        </p>
                        <p className="text-3xl font-bold">
                            {formatDecimal(minCriterion.group_average)}
                        </p>
                        <p className="mt-2 text-xs text-muted-foreground">
                            Este criterio puede necesitar mayor énfasis
                            pedagógico en el grupo.
                        </p>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function StatisticsShow({ programming, statistics }: Props) {
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
                    description={`${programming.period}${programming.group ? ` · Grupo ${programming.group}` : ''} · ${programming.modality?.name ?? ''}`}
                >
                    <a
                        href={GradingController.downloadReport.url(programming)}
                        download
                        className="inline-flex items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent"
                    >
                        ↓ Exportar reporte Excel
                    </a>
                </PageHeader>

                <Tabs defaultValue="summary">
                    <TabsList className="mb-2">
                        <TabsTrigger value="summary">
                            Resumen global
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
                        <SummaryTab summary={statistics.summary} />
                    </TabsContent>

                    <TabsContent value="by-student" className="mt-4">
                        <ByStudentTab
                            byStudent={statistics.byStudent}
                            byCriterion={statistics.byCriterion}
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
