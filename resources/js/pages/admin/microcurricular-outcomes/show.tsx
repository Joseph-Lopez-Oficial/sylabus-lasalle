import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart2,
    ChevronDown,
    ChevronRight,
    TrendingDown,
    TrendingUp,
} from 'lucide-react';
import { useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
    Legend,
} from 'recharts';
import * as OutcomeController from '@/actions/App/Http/Controllers/Admin/MicrocurricularLearningOutcomeController';
import { DownloadButton } from '@/components/download-button';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/admin-layout';
import {
    gradeTextClass,
    levelBadgeClass,
    levelColor,
    levelLabelForGrade,
    setActiveScale,
} from '@/lib/grading-scale';
import { formatDecimal } from '@/lib/utils';
import type { BreadcrumbItem, ScaleLevel } from '@/types';

// ── Types ─────────────────────────────────────────────────────────────────────

type LevelDist = {
    level_id: number;
    level_name: string;
    count: number;
    percentage: number;
};

type ProgrammingRow = {
    programming_id: number;
    period: string;
    group: string | null;
    academic_space: { id: number; name: string; code: string } | null;
    professor: { first_name: string; last_name: string } | null;
    student_count: number;
    group_average: number;
    highest: number;
    lowest: number;
    distribution: LevelDist[];
};

type TrendPoint = { period: string; average: number };

type OutcomeDetail = {
    id: number;
    code: string | null;
    description: string;
    is_active: boolean;
    type: { id: number; name: string } | null;
    academic_space: {
        id: number;
        name: string;
        code: string;
        competency: {
            name: string;
            problematic_nucleus: {
                name: string;
                program: {
                    name: string;
                    faculty: { name: string } | null;
                } | null;
            } | null;
        } | null;
    } | null;
};

type Props = {
    outcome: OutcomeDetail;
    summary: {
        global_average: number;
        total_programmings: number;
        total_grade_records: number;
        distribution: LevelDist[];
        trend_by_period: TrendPoint[];
    };
    by_programming: ProgrammingRow[];
    scale: ScaleLevel[];
};

// ── Constants ─────────────────────────────────────────────────────────────────

const LEVEL_COLOR_LIST = ['#ef4444', '#f97316', '#22c55e', '#3b82f6'];

const TYPE_COLORS: Record<string, string> = {
    Conocimiento: '#6366f1',
    Habilidad: '#10b981',
    Actitud: '#f59e0b',
};
const TYPE_FALLBACK = '#8b5cf6';

const gradeColor = gradeTextClass;
const levelLabel = levelLabelForGrade;

// ── Main page ─────────────────────────────────────────────────────────────────

export default function MicrocurricularOutcomeShow({
    outcome,
    summary,
    by_programming,
    scale,
}: Props) {
    // Set before the tree renders, so nested components label grades from the
    // configured scale rather than fixed thresholds.
    setActiveScale(scale);

    const [expandedRow, setExpandedRow] = useState<number | null>(null);

    const typeColor = TYPE_COLORS[outcome.type?.name ?? ''] ?? TYPE_FALLBACK;
    const minProgramming = [...by_programming].sort(
        (a, b) => a.group_average - b.group_average,
    )[0];

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Resultados Microcurriculares',
            href: OutcomeController.index.url(),
        },
        {
            title: outcome.code ?? `RA${outcome.id}`,
            href: OutcomeController.show.url(outcome),
        },
    ];

    // Donut data
    const donutData = summary.distribution.map((d) => ({
        name: d.level_name,
        value: d.count,
        percentage: d.percentage,
    }));

    // Bar comparison data
    const comparisonData = by_programming.map((p) => ({
        name: `${p.period}${p.group ? ` G${p.group}` : ''}`,
        fullName: `${p.academic_space?.name ?? ''} · ${p.period}${p.group ? ` · Grupo ${p.group}` : ''}`,
        promedio: p.group_average,
        profesor: `${p.professor?.first_name ?? ''} ${p.professor?.last_name ?? ''}`,
        id: p.programming_id,
    }));

    // Trend
    const trendData = summary.trend_by_period.map((t) => ({
        period: t.period,
        promedio: t.average,
    }));
    const hasTrend = trendData.length > 1;
    const trendUp =
        hasTrend &&
        trendData[trendData.length - 1].promedio > trendData[0].promedio;

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`${outcome.code ?? 'RA'} — Detalle`} />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <PageHeader
                    title={outcome.code ?? `RA${outcome.id}`}
                    description={outcome.academic_space?.name ?? ''}
                >
                    {summary.total_grade_records > 0 && (
                        <DownloadButton
                            href={OutcomeController.downloadReport.url(outcome)}
                        >
                            Exportar Excel
                        </DownloadButton>
                    )}
                    <Button variant="outline" asChild>
                        <Link href={OutcomeController.index.url()}>
                            ← Volver
                        </Link>
                    </Button>
                </PageHeader>

                {/* Ficha del resultado */}
                <Card
                    className="border-l-4"
                    style={{ borderLeftColor: typeColor }}
                >
                    <CardContent className="pt-5">
                        <div className="flex flex-wrap items-start gap-4">
                            <span
                                className="shrink-0 rounded-full px-3 py-1 text-sm font-bold text-white"
                                style={{ backgroundColor: typeColor }}
                            >
                                {outcome.type?.name ?? 'Sin tipo'}
                            </span>
                            <p className="flex-1 text-sm leading-relaxed">
                                {outcome.description}
                            </p>
                        </div>
                        {/* Jerarquía */}
                        <div className="mt-3 flex flex-wrap items-center gap-1 text-xs text-muted-foreground">
                            {outcome.academic_space?.competency
                                ?.problematic_nucleus?.program?.faculty
                                ?.name && (
                                <>
                                    <span>
                                        {
                                            outcome.academic_space.competency
                                                .problematic_nucleus.program
                                                .faculty.name
                                        }
                                    </span>
                                    <ChevronRight className="h-3 w-3" />
                                </>
                            )}
                            {outcome.academic_space?.competency
                                ?.problematic_nucleus?.program?.name && (
                                <>
                                    <span>
                                        {
                                            outcome.academic_space.competency
                                                .problematic_nucleus.program
                                                .name
                                        }
                                    </span>
                                    <ChevronRight className="h-3 w-3" />
                                </>
                            )}
                            {outcome.academic_space?.competency
                                ?.problematic_nucleus?.name && (
                                <>
                                    <span>
                                        {
                                            outcome.academic_space.competency
                                                .problematic_nucleus.name
                                        }
                                    </span>
                                    <ChevronRight className="h-3 w-3" />
                                </>
                            )}
                            {outcome.academic_space?.competency?.name && (
                                <>
                                    <span>
                                        {outcome.academic_space.competency.name}
                                    </span>
                                    <ChevronRight className="h-3 w-3" />
                                </>
                            )}
                            {outcome.academic_space?.name && (
                                <span className="font-medium text-foreground">
                                    {outcome.academic_space.name}
                                </span>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* KPIs globales */}
                {summary.total_grade_records === 0 ? (
                    <Card className="border-amber-200 bg-amber-50/30">
                        <CardContent className="pt-5">
                            <div className="flex items-center gap-3">
                                <AlertTriangle className="h-5 w-5 text-amber-500" />
                                <p className="text-sm font-medium text-amber-800">
                                    Este resultado aún no tiene calificaciones
                                    registradas en ninguna programación.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardContent className="pt-5 text-center">
                                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        Promedio global
                                    </p>
                                    <p
                                        className={`mt-1 text-4xl font-bold ${gradeColor(summary.global_average)}`}
                                    >
                                        {formatDecimal(summary.global_average)}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {levelLabel(summary.global_average)} ·
                                        sobre 5.0
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="pt-5 text-center">
                                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        Grupos calificados
                                    </p>
                                    <p className="mt-1 text-4xl font-bold">
                                        {summary.total_programmings}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        programaciones distintas
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="pt-5 text-center">
                                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        Calificaciones totales
                                    </p>
                                    <p className="mt-1 text-4xl font-bold">
                                        {summary.total_grade_records}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        registros en la base de datos
                                    </p>
                                </CardContent>
                            </Card>
                            <Card className={hasTrend ? '' : ''}>
                                <CardContent className="pt-5 text-center">
                                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        Tendencia
                                    </p>
                                    {hasTrend ? (
                                        <>
                                            {trendUp ? (
                                                <TrendingUp className="mx-auto mt-1 h-10 w-10 text-green-500" />
                                            ) : (
                                                <TrendingDown className="mx-auto mt-1 h-10 w-10 text-red-500" />
                                            )}
                                            <p
                                                className={`text-xs font-medium ${trendUp ? 'text-green-600' : 'text-red-600'}`}
                                            >
                                                {trendUp
                                                    ? 'Mejorando'
                                                    : 'Bajando'}{' '}
                                                entre períodos
                                            </p>
                                        </>
                                    ) : (
                                        <p className="mt-3 text-xs text-muted-foreground">
                                            Solo un período registrado
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Distribución global */}
                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Distribución global de calificaciones
                                    </CardTitle>
                                    <p className="text-xs text-muted-foreground">
                                        Total de {summary.total_grade_records}{' '}
                                        calificaciones registradas en todos los
                                        grupos
                                    </p>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer
                                        width="100%"
                                        height={220}
                                    >
                                        <PieChart>
                                            <Pie
                                                data={donutData}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={55}
                                                outerRadius={88}
                                                paddingAngle={3}
                                                dataKey="value"
                                            >
                                                {donutData.map((_, i) => (
                                                    <Cell
                                                        key={i}
                                                        fill={
                                                            levelColor(donutData[i]
                                                                    .name) ??
                                                            LEVEL_COLOR_LIST[
                                                                i % 4
                                                            ]
                                                        }
                                                    />
                                                ))}
                                            </Pie>
                                            <Tooltip
                                                formatter={(v, name) => [
                                                    `${v} calificaciones (${donutData.find((d) => d.name === name)?.percentage ?? 0}%)`,
                                                    name,
                                                ]}
                                            />
                                            <Legend
                                                formatter={(value, entry) => (
                                                    <span className="text-xs">
                                                        {value}:{' '}
                                                        {(
                                                            entry.payload as {
                                                                percentage?: number;
                                                            }
                                                        ).percentage ?? 0}
                                                        %
                                                    </span>
                                                )}
                                            />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>

                            {/* Tendencia por período */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Promedio por período académico
                                    </CardTitle>
                                    <p className="text-xs text-muted-foreground">
                                        Evolución del promedio del grupo en este
                                        RA a través del tiempo
                                    </p>
                                </CardHeader>
                                <CardContent>
                                    {trendData.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            Sin datos de tendencia
                                        </p>
                                    ) : (
                                        <ResponsiveContainer
                                            width="100%"
                                            height={220}
                                        >
                                            <BarChart data={trendData}>
                                                <CartesianGrid
                                                    strokeDasharray="3 3"
                                                    vertical={false}
                                                />
                                                <XAxis
                                                    dataKey="period"
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
                                                        'Promedio',
                                                    ]}
                                                />
                                                <Bar
                                                    dataKey="promedio"
                                                    radius={[4, 4, 0, 0]}
                                                >
                                                    {trendData.map((d, i) => (
                                                        <Cell
                                                            key={i}
                                                            fill={
                                                                levelColor(levelLabel(
                                                                        d.promedio,
                                                                    )) ?? typeColor
                                                            }
                                                        />
                                                    ))}
                                                </Bar>
                                            </BarChart>
                                        </ResponsiveContainer>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Comparación entre programaciones */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <BarChart2
                                        className="h-4 w-4"
                                        style={{ color: typeColor }}
                                    />
                                    Comparación de promedio entre grupos y
                                    períodos
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">
                                    Cada barra es una programación donde este RA
                                    fue calificado · clic en la fila para ver
                                    distribución
                                </p>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer
                                    width="100%"
                                    height={Math.max(
                                        200,
                                        comparisonData.length * 40,
                                    )}
                                >
                                    <BarChart
                                        data={comparisonData}
                                        layout="vertical"
                                    >
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            horizontal={false}
                                        />
                                        <XAxis
                                            type="number"
                                            domain={[1, 5]}
                                            tickCount={5}
                                            tick={{ fontSize: 10 }}
                                        />
                                        <YAxis
                                            type="category"
                                            dataKey="name"
                                            tick={{ fontSize: 11 }}
                                            width={90}
                                        />
                                        <Tooltip
                                            formatter={(v) => [
                                                `${formatDecimal(Number(v))} — ${levelLabel(Number(v))}`,
                                                'Promedio',
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
                                            dataKey="promedio"
                                            radius={[0, 4, 4, 0]}
                                        >
                                            {comparisonData.map((e, i) => (
                                                <Cell
                                                    key={i}
                                                    fill={
                                                        levelColor(levelLabel(
                                                                e.promedio,
                                                            )) ?? typeColor
                                                    }
                                                    opacity={
                                                        e.id ===
                                                        minProgramming?.programming_id
                                                            ? 0.9
                                                            : 0.65
                                                    }
                                                />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>

                        {/* Tabla detallada por programación */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Detalle por programación
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">
                                    Ordenado de mayor a menor promedio · la fila
                                    con menor promedio aparece resaltada · clic
                                    para ver distribución de niveles
                                </p>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-1.5">
                                    {by_programming.map((p) => {
                                        const isMin =
                                            p.programming_id ===
                                            minProgramming?.programming_id;
                                        const isExp =
                                            expandedRow === p.programming_id;
                                        return (
                                            <div
                                                key={p.programming_id}
                                                className={`overflow-hidden rounded-lg border ${isMin ? 'border-red-200 dark:border-red-800' : ''}`}
                                            >
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setExpandedRow(
                                                            isExp
                                                                ? null
                                                                : p.programming_id,
                                                        )
                                                    }
                                                    className={`flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/40 ${isMin ? 'bg-red-50/50 dark:bg-red-950/10' : 'bg-background'}`}
                                                >
                                                    {/* Período y grupo */}
                                                    <div className="w-20 shrink-0">
                                                        <p className="text-xs font-semibold">
                                                            {p.period}
                                                        </p>
                                                        {p.group && (
                                                            <p className="text-xs text-muted-foreground">
                                                                Grupo {p.group}
                                                            </p>
                                                        )}
                                                    </div>

                                                    {/* Espacio + profesor */}
                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate text-sm font-medium">
                                                            {p.academic_space
                                                                ?.name ?? '—'}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {p.professor
                                                                ? `Prof. ${p.professor.first_name} ${p.professor.last_name}`
                                                                : '—'}{' '}
                                                            · {p.student_count}{' '}
                                                            estudiante
                                                            {p.student_count !==
                                                            1
                                                                ? 's'
                                                                : ''}
                                                        </p>
                                                    </div>

                                                    {/* Métricas */}
                                                    <div className="hidden shrink-0 items-center gap-4 text-xs text-muted-foreground sm:flex">
                                                        <span>
                                                            ↑{' '}
                                                            {formatDecimal(
                                                                p.highest,
                                                            )}
                                                        </span>
                                                        <span>
                                                            ↓{' '}
                                                            {formatDecimal(
                                                                p.lowest,
                                                            )}
                                                        </span>
                                                    </div>

                                                    {/* Promedio */}
                                                    <div className="flex shrink-0 items-center gap-2">
                                                        {isMin && (
                                                            <Badge
                                                                variant="destructive"
                                                                className="hidden text-xs sm:inline-flex"
                                                            >
                                                                menor
                                                            </Badge>
                                                        )}
                                                        <span
                                                            className={`w-12 text-right text-sm font-bold ${gradeColor(p.group_average)}`}
                                                        >
                                                            {formatDecimal(
                                                                p.group_average,
                                                            )}
                                                        </span>
                                                        {isExp ? (
                                                            <ChevronDown className="h-4 w-4 rotate-180 text-muted-foreground transition-transform" />
                                                        ) : (
                                                            <ChevronDown className="h-4 w-4 text-muted-foreground transition-transform" />
                                                        )}
                                                    </div>
                                                </button>

                                                {/* Distribución expandida */}
                                                {isExp && (
                                                    <div className="border-t bg-muted/10 px-4 pt-3 pb-4">
                                                        <p className="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                            Distribución de
                                                            calificaciones en
                                                            esta programación
                                                        </p>
                                                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                                            {p.distribution.map(
                                                                (d) => (
                                                                    <div
                                                                        key={
                                                                            d.level_id
                                                                        }
                                                                        className="rounded-lg border bg-background p-3"
                                                                    >
                                                                        <div className="flex items-center justify-between gap-2">
                                                                            <span
                                                                                className="text-xs font-medium"
                                                                                style={{
                                                                                    color:
                                                                                        levelColor(d
                                                                                                .level_name) ??
                                                                                        '#ccc',
                                                                                }}
                                                                            >
                                                                                {
                                                                                    d.level_name
                                                                                }
                                                                            </span>
                                                                            <span
                                                                                className={`rounded px-1.5 py-0.5 text-xs font-bold ${levelBadgeClass(
                                                                                    d.level_name,
                                                                                )}`}
                                                                            >
                                                                                {
                                                                                    d.count
                                                                                }
                                                                            </span>
                                                                        </div>
                                                                        <div className="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                                                            <div
                                                                                className="h-full rounded-full"
                                                                                style={{
                                                                                    width: `${d.percentage}%`,
                                                                                    backgroundColor:
                                                                                        levelColor(d
                                                                                                .level_name) ??
                                                                                        '#ccc',
                                                                                }}
                                                                            />
                                                                        </div>
                                                                        <p className="mt-0.5 text-right text-xs text-muted-foreground">
                                                                            {
                                                                                d.percentage
                                                                            }
                                                                            %
                                                                        </p>
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
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
