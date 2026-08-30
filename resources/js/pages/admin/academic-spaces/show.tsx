import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart2,
    BookOpen,
    ChevronDown,
    ChevronRight,
    ChevronUp,
    Download,
    Pencil,
    Plus,
    TrendingDown,
    TrendingUp,
    Zap,
} from 'lucide-react';
import { useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import * as SpaceController from '@/actions/App/Http/Controllers/Admin/AcademicSpaceController';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    AcademicSpace,
    BreadcrumbItem,
    MicrocurricularLearningOutcome,
    MicrocurricularLearningOutcomeType,
    Programming,
    ScaleLevel,
    Topic,
} from '@/types';

type AcademicSpaceWithRelations = AcademicSpace & {
    competency: { id: number; name: string };
    microcurricular_learning_outcomes: (MicrocurricularLearningOutcome & {
        type: MicrocurricularLearningOutcomeType;
    })[];
    topics: (Topic & {
        activities?: {
            id: number;
            name: string;
            products?: { id: number; name: string }[];
        }[];
    })[];
    programmings: (Programming & {
        professor?: { first_name: string; last_name: string };
    })[];
};

// ── Stats types ───────────────────────────────────────────────────────────────

type LevelDist = {
    level_id: number;
    level_name: string;
    count: number;
    percentage: number;
};

type ProgRow = {
    programming_id: number;
    period: string;
    group: string | null;
    professor: { first_name: string; last_name: string } | null;
    student_count: number;
    group_average: number;
    highest: number;
    lowest: number;
    distribution: LevelDist[];
};

type OutcomeRow = {
    outcome_id: number;
    outcome_code: string | null;
    outcome_desc: string;
    type_id: number;
    type_name: string | null;
    group_average: number;
    highest: number;
    lowest: number;
    distribution: LevelDist[];
    programming_count: number;
};

type CriterionRow = {
    criterion_id: number;
    criterion_name: string;
    type_id: number;
    type_name: string | null;
    group_average: number;
};

type SpaceStats = {
    summary: {
        global_average: number;
        total_programmings: number;
        total_grade_records: number;
        distribution: LevelDist[];
        trend_by_period: { period: string; average: number }[];
    };
    by_programming: ProgRow[];
    by_outcome: OutcomeRow[];
    by_criterion: CriterionRow[];
} | null;

type Props = {
    academicSpace: AcademicSpaceWithRelations;
    statistics: SpaceStats;
    scale: ScaleLevel[];
};

// ── Stats constants ───────────────────────────────────────────────────────────

const LEVEL_LIST = ['#ef4444', '#f97316', '#22c55e', '#3b82f6'];
const TYPE_COLORS: Record<string, string> = {
    Conocimiento: '#6366f1',
    Habilidad: '#10b981',
    Actitud: '#f59e0b',
};
const TYPE_FALLBACK = '#8b5cf6';

const gc = gradeTextClass;
const gbg = gradeBadgeClass;
const ll = levelLabelForGrade;

export default function AcademicSpacesShow({
    academicSpace,
    statistics,
    scale,
}: Props) {
    // Set before the tree renders, so nested components colour levels from the
    // configured scale rather than from hardcoded names.
    setActiveScale(scale);

    const [expandedTopics, setExpandedTopics] = useState<Set<number>>(
        new Set(),
    );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Espacios Académicos', href: SpaceController.index.url() },
        {
            title: academicSpace.name,
            href: SpaceController.show.url(academicSpace),
        },
    ];

    // Group outcomes by type
    const outcomesByType =
        academicSpace.microcurricular_learning_outcomes.reduce<
            Record<
                string,
                {
                    type: MicrocurricularLearningOutcomeType;
                    outcomes: MicrocurricularLearningOutcome[];
                }
            >
        >((acc, outcome) => {
            const typeId = String(outcome.type_id);
            if (!acc[typeId]) {
                acc[typeId] = { type: outcome.type!, outcomes: [] };
            }
            acc[typeId].outcomes.push(outcome);
            return acc;
        }, {});

    function toggleTopic(id: number) {
        setExpandedTopics((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={academicSpace.name} />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title={academicSpace.name}
                    description={`${academicSpace.code} · ${academicSpace.credits} créditos${academicSpace.semester ? ` · Semestre ${academicSpace.semester}` : ''}`}
                >
                    <StatusBadge isActive={academicSpace.is_active} />
                    {statistics && (
                        <Button variant="outline" asChild>
                            <a
                                href={`/admin/academic-spaces/${academicSpace.id}/statistics/export`}
                                download
                            >
                                <Download className="mr-2 h-4 w-4" />
                                Exportar Estadísticas
                            </a>
                        </Button>
                    )}
                    <Button variant="outline" asChild>
                        <Link href={SpaceController.edit.url(academicSpace)}>
                            <Pencil className="mr-2 h-4 w-4" />
                            Editar
                        </Link>
                    </Button>
                </PageHeader>

                <Tabs defaultValue="info">
                    <TabsList>
                        <TabsTrigger value="info">
                            Información general
                        </TabsTrigger>
                        <TabsTrigger value="outcomes">
                            Resultados microcurriculares
                            <Badge variant="secondary" className="ml-2">
                                {
                                    academicSpace
                                        .microcurricular_learning_outcomes
                                        .length
                                }
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="topics">
                            Temas
                            <Badge variant="secondary" className="ml-2">
                                {academicSpace.topics.length}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="programmings">
                            Programaciones
                            <Badge variant="secondary" className="ml-2">
                                {academicSpace.programmings.length}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger
                            value="statistics"
                            className="flex items-center gap-1.5"
                        >
                            <BarChart2 className="h-3.5 w-3.5" />
                            Estadísticas
                        </TabsTrigger>
                    </TabsList>

                    {/* Tab: Info general */}
                    <TabsContent value="info" className="mt-4">
                        <Card className="max-w-2xl">
                            <CardContent className="space-y-3 pt-6">
                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p className="text-muted-foreground">
                                            Código
                                        </p>
                                        <p className="font-mono font-medium">
                                            {academicSpace.code}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">
                                            Créditos
                                        </p>
                                        <p className="font-medium">
                                            {academicSpace.credits}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">
                                            Semestre
                                        </p>
                                        <p className="font-medium">
                                            {academicSpace.semester ?? '—'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">
                                            Competencia
                                        </p>
                                        <p className="font-medium">
                                            {academicSpace.competency.name}
                                        </p>
                                    </div>
                                </div>
                                {academicSpace.description && (
                                    <div className="pt-2 text-sm">
                                        <p className="text-muted-foreground">
                                            Descripción
                                        </p>
                                        <p className="mt-1">
                                            {academicSpace.description}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Tab: Resultados microcurriculares */}
                    <TabsContent value="outcomes" className="mt-4 space-y-4">
                        <div className="flex justify-end">
                            <Button asChild size="sm">
                                <Link
                                    href={`/admin/microcurricular-outcomes/create?academic_space_id=${academicSpace.id}`}
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nuevo resultado
                                </Link>
                            </Button>
                        </div>
                        {Object.keys(outcomesByType).length === 0 ? (
                            <EmptyState
                                title="Sin resultados"
                                description="No hay resultados microcurriculares registrados."
                            />
                        ) : (
                            Object.values(outcomesByType).map(
                                ({ type, outcomes }) => (
                                    <Card key={type.id}>
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-base">
                                                {type.name}
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-2">
                                            {outcomes.map((o) => (
                                                <div
                                                    key={o.id}
                                                    className="flex items-start justify-between gap-2 rounded-md border p-3 text-sm"
                                                >
                                                    <p className="flex-1">
                                                        {o.description}
                                                    </p>
                                                    <div className="flex shrink-0 items-center gap-1">
                                                        <StatusBadge
                                                            isActive={
                                                                o.is_active
                                                            }
                                                        />
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/admin/microcurricular-outcomes/${o.id}/edit`}
                                                            >
                                                                <Pencil className="h-3.5 w-3.5" />
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </div>
                                            ))}
                                        </CardContent>
                                    </Card>
                                ),
                            )
                        )}
                    </TabsContent>

                    {/* Tab: Temas */}
                    <TabsContent value="topics" className="mt-4 space-y-3">
                        <div className="flex justify-end">
                            <Button asChild size="sm">
                                <Link
                                    href={`/admin/topics/create?academic_space_id=${academicSpace.id}`}
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nuevo tema
                                </Link>
                            </Button>
                        </div>
                        {academicSpace.topics.length === 0 ? (
                            <EmptyState
                                title="Sin temas"
                                description="No hay temas registrados para este espacio."
                            />
                        ) : (
                            academicSpace.topics.map((topic) => (
                                <Card key={topic.id}>
                                    <div
                                        className="flex cursor-pointer items-center gap-2 p-4"
                                        onClick={() => toggleTopic(topic.id)}
                                    >
                                        {expandedTopics.has(topic.id) ? (
                                            <ChevronDown className="h-4 w-4 shrink-0" />
                                        ) : (
                                            <ChevronRight className="h-4 w-4 shrink-0" />
                                        )}
                                        <span className="flex-1 font-medium">
                                            {topic.name}
                                        </span>
                                        <StatusBadge
                                            isActive={topic.is_active}
                                        />
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            asChild
                                            onClick={(e) => e.stopPropagation()}
                                        >
                                            <Link
                                                href={`/admin/topics/${topic.id}/edit`}
                                            >
                                                <Pencil className="h-3.5 w-3.5" />
                                            </Link>
                                        </Button>
                                    </div>
                                    {expandedTopics.has(topic.id) &&
                                        topic.activities && (
                                            <CardContent className="space-y-1.5 pt-0">
                                                {topic.activities.map(
                                                    (activity) => (
                                                        <div
                                                            key={activity.id}
                                                            className="ml-6 rounded-md border p-2.5 text-sm"
                                                        >
                                                            <span>
                                                                {activity.name}
                                                            </span>
                                                        </div>
                                                    ),
                                                )}
                                                <div className="ml-6">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/admin/activities/create?topic_id=${topic.id}`}
                                                        >
                                                            <Plus className="mr-1.5 h-3.5 w-3.5" />
                                                            Agregar actividad
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </CardContent>
                                        )}
                                </Card>
                            ))
                        )}
                    </TabsContent>

                    {/* Tab: Programaciones */}
                    <TabsContent value="programmings" className="mt-4">
                        {academicSpace.programmings.length === 0 ? (
                            <EmptyState
                                title="Sin programaciones"
                                description="No hay programaciones activas para este espacio académico."
                                icon={BookOpen}
                            />
                        ) : (
                            <div className="grid gap-3 md:grid-cols-2">
                                {academicSpace.programmings.map((p) => (
                                    <Card key={p.id}>
                                        <CardContent className="pt-4">
                                            <p className="font-medium">
                                                {p.academic_period?.name ?? '—'}
                                                {p.group
                                                    ? ` · Grupo ${p.group}`
                                                    : ''}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {p.professor
                                                    ? `${p.professor.first_name} ${p.professor.last_name}`
                                                    : 'Sin profesor asignado'}
                                            </p>
                                            <StatusBadge
                                                isActive={p.is_active}
                                                className="mt-2"
                                            />
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}
                    </TabsContent>
                    {/* Tab: Estadísticas */}
                    <TabsContent value="statistics" className="mt-4">
                        <AcademicSpaceStats statistics={statistics} />
                    </TabsContent>
                </Tabs>
            </div>
        </AdminLayout>
    );
}

// ── Statistics component ──────────────────────────────────────────────────────

function AcademicSpaceStats({ statistics }: { statistics: SpaceStats }) {
    const [expandedProg, setExpandedProg] = useState<number | null>(null);
    const [activeSection, setActiveSection] = useState<
        'summary' | 'programmings' | 'outcomes' | 'criteria'
    >('summary');

    if (!statistics) {
        return (
            <Card className="border-amber-200 bg-amber-50/30 dark:border-amber-800 dark:bg-amber-950/10">
                <CardContent className="pt-5">
                    <div className="flex items-center gap-3">
                        <AlertTriangle className="h-5 w-5 text-amber-500" />
                        <p className="text-sm font-medium text-amber-800 dark:text-amber-300">
                            Este espacio académico aún no tiene calificaciones
                            registradas en ninguna programación.
                        </p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    const { summary, by_programming, by_outcome, by_criterion } = statistics;
    const minProg = [...by_programming].sort(
        (a, b) => a.group_average - b.group_average,
    )[0];
    const maxProg = by_programming[0];
    const minOutcome = [...by_outcome].sort(
        (a, b) => a.group_average - b.group_average,
    )[0];
    const minCriterion = [...by_criterion].sort(
        (a, b) => a.group_average - b.group_average,
    )[0];

    const hasTrend = summary.trend_by_period.length > 1;
    const trendUp =
        hasTrend &&
        summary.trend_by_period[summary.trend_by_period.length - 1].average >
            summary.trend_by_period[0].average;

    const donutData = summary.distribution.map((d) => ({
        name: d.level_name,
        value: d.count,
        percentage: d.percentage,
    }));

    // Sub-tabs
    const sections: { key: typeof activeSection; label: string }[] = [
        { key: 'summary', label: 'Resumen global' },
        { key: 'programmings', label: 'Por programación' },
        { key: 'outcomes', label: 'Por resultado (RA)' },
        { key: 'criteria', label: 'Por criterio' },
    ];

    return (
        <div className="space-y-4">
            {/* Sub-navigation */}
            <div className="flex flex-wrap gap-1 rounded-lg border bg-muted/30 p-1">
                {sections.map((s) => (
                    <button
                        key={s.key}
                        type="button"
                        onClick={() => setActiveSection(s.key)}
                        className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${activeSection === s.key ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                    >
                        {s.label}
                    </button>
                ))}
            </div>

            {/* ── Resumen global ── */}
            {activeSection === 'summary' && (
                <div className="space-y-6">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            {
                                label: 'Promedio global del espacio',
                                value: formatDecimal(summary.global_average),
                                sub: `${ll(summary.global_average)} · promedio de todos los grupos`,
                                color: gc(summary.global_average),
                            },
                            {
                                label: 'Grupos calificados',
                                value: String(summary.total_programmings),
                                sub: 'programaciones con calificaciones',
                                color: 'text-foreground',
                            },
                            {
                                label: 'Calificaciones totales',
                                value: String(summary.total_grade_records),
                                sub: 'registros en BD (criterio × RA × est.)',
                                color: 'text-foreground',
                            },
                            {
                                label: 'Tendencia',
                                value: hasTrend
                                    ? trendUp
                                        ? 'Mejorando'
                                        : 'Bajando'
                                    : 'Sin comparar',
                                sub: hasTrend
                                    ? `${summary.trend_by_period[0].period} → ${summary.trend_by_period[summary.trend_by_period.length - 1].period}`
                                    : 'Solo un período registrado',
                                color: !hasTrend
                                    ? 'text-muted-foreground'
                                    : trendUp
                                      ? 'text-green-600'
                                      : 'text-red-600',
                            },
                        ].map((k) => (
                            <Card key={k.label}>
                                <CardContent className="pt-5 text-center">
                                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        {k.label}
                                    </p>
                                    <div className="mt-1 flex items-center justify-center gap-2">
                                        {k.label === 'Tendencia' &&
                                            hasTrend &&
                                            (trendUp ? (
                                                <TrendingUp className="h-5 w-5 text-green-500" />
                                            ) : (
                                                <TrendingDown className="h-5 w-5 text-red-500" />
                                            ))}
                                        <p
                                            className={`text-3xl font-bold ${k.color}`}
                                        >
                                            {k.value}
                                        </p>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {k.sub}
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Distribución global de calificaciones
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">
                                    Total de {summary.total_grade_records}{' '}
                                    calificaciones a través de todos los grupos
                                </p>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={220}>
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
                                                        levelColor(donutData[i].name) ?? LEVEL_LIST[i % 4]
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

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Promedio por período académico
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">
                                    Evolución del promedio del espacio a través
                                    del tiempo
                                </p>
                            </CardHeader>
                            <CardContent>
                                {summary.trend_by_period.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Sin datos de tendencia
                                    </p>
                                ) : (
                                    <ResponsiveContainer
                                        width="100%"
                                        height={220}
                                    >
                                        <BarChart
                                            data={summary.trend_by_period.map(
                                                (t) => ({
                                                    period: t.period,
                                                    promedio: t.average,
                                                }),
                                            )}
                                        >
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
                                                    `${formatDecimal(Number(v))} — ${ll(Number(v))}`,
                                                    'Promedio',
                                                ]}
                                            />
                                            <Bar
                                                dataKey="promedio"
                                                radius={[4, 4, 0, 0]}
                                            >
                                                {summary.trend_by_period.map(
                                                    (t, i) => (
                                                        <Cell
                                                            key={i}
                                                            fill={
                                                                levelColor(ll(
                                                                        t.average,
                                                                    )) ?? '#6366f1'
                                                            }
                                                        />
                                                    ),
                                                )}
                                            </Bar>
                                        </BarChart>
                                    </ResponsiveContainer>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        {[
                            {
                                label: 'Mejor grupo',
                                prog: maxProg,
                                color: 'border-green-200',
                            },
                            {
                                label: 'Peor grupo',
                                prog: minProg,
                                color: 'border-red-200',
                            },
                            {
                                label: 'RA más difícil',
                                outcome: minOutcome,
                                color: 'border-amber-200',
                            },
                        ].map((item) => (
                            <Card
                                key={item.label}
                                className={`border-l-4 ${item.color}`}
                            >
                                <CardContent className="pt-4">
                                    <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        {item.label}
                                    </p>
                                    {item.prog && (
                                        <>
                                            <p className="mt-1 text-sm font-medium">
                                                {item.prog.period}
                                                {item.prog.group
                                                    ? ` · G${item.prog.group}`
                                                    : ''}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {item.prog.professor
                                                    ? `${item.prog.professor.first_name} ${item.prog.professor.last_name}`
                                                    : '—'}
                                            </p>
                                            <p
                                                className={`mt-1 text-2xl font-bold ${gc(item.prog.group_average)}`}
                                            >
                                                {formatDecimal(
                                                    item.prog.group_average,
                                                )}
                                            </p>
                                        </>
                                    )}
                                    {item.outcome && (
                                        <>
                                            <p className="mt-1 text-sm font-medium">
                                                {item.outcome.outcome_code ??
                                                    `RA${item.outcome.outcome_id}`}
                                            </p>
                                            <p className="line-clamp-2 text-xs text-muted-foreground">
                                                {item.outcome.outcome_desc}
                                            </p>
                                            <p
                                                className={`mt-1 text-2xl font-bold ${gc(item.outcome.group_average)}`}
                                            >
                                                {formatDecimal(
                                                    item.outcome.group_average,
                                                )}
                                            </p>
                                        </>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            )}

            {/* ── Por programación ── */}
            {activeSection === 'programmings' && (
                <div className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Comparación de promedio entre grupos
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                Ordenado de mayor a menor · clic en cada fila
                                para ver la distribución de niveles de ese grupo
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div className="mb-4">
                                <ResponsiveContainer
                                    width="100%"
                                    height={Math.max(
                                        150,
                                        by_programming.length * 42,
                                    )}
                                >
                                    <BarChart
                                        data={[...by_programming]
                                            .reverse()
                                            .map((p) => ({
                                                name: `${p.period}${p.group ? ` G${p.group}` : ''}`,
                                                promedio: p.group_average,
                                            }))}
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
                                            width={80}
                                        />
                                        <Tooltip
                                            formatter={(v) => [
                                                `${formatDecimal(Number(v))} — ${ll(Number(v))}`,
                                                'Promedio',
                                            ]}
                                        />
                                        <Bar
                                            dataKey="promedio"
                                            radius={[0, 4, 4, 0]}
                                        >
                                            {[...by_programming]
                                                .reverse()
                                                .map((p, i) => (
                                                    <Cell
                                                        key={i}
                                                        fill={
                                                            levelColor(ll(
                                                                    p.group_average,
                                                                )) ?? '#6366f1'
                                                        }
                                                        opacity={
                                                            p.programming_id ===
                                                            minProg?.programming_id
                                                                ? 0.9
                                                                : 0.65
                                                        }
                                                    />
                                                ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                            <div className="space-y-1.5">
                                {by_programming.map((p) => {
                                    const isMin =
                                        p.programming_id ===
                                        minProg?.programming_id;
                                    const isExp =
                                        expandedProg === p.programming_id;
                                    return (
                                        <div
                                            key={p.programming_id}
                                            className={`overflow-hidden rounded-lg border ${isMin ? 'border-red-200 dark:border-red-800' : ''}`}
                                        >
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setExpandedProg(
                                                        isExp
                                                            ? null
                                                            : p.programming_id,
                                                    )
                                                }
                                                className={`flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/40 ${isMin ? 'bg-red-50/50 dark:bg-red-950/10' : 'bg-background'}`}
                                            >
                                                <div className="w-24 shrink-0">
                                                    <p className="text-xs font-semibold">
                                                        {p.period}
                                                    </p>
                                                    {p.group && (
                                                        <p className="text-xs text-muted-foreground">
                                                            Grupo {p.group}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-medium">
                                                        {p.professor
                                                            ? `Prof. ${p.professor.first_name} ${p.professor.last_name}`
                                                            : '—'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {p.student_count}{' '}
                                                        estudiante
                                                        {p.student_count !== 1
                                                            ? 's'
                                                            : ''}{' '}
                                                        · ↑
                                                        {formatDecimal(
                                                            p.highest,
                                                        )}{' '}
                                                        ↓
                                                        {formatDecimal(
                                                            p.lowest,
                                                        )}
                                                    </p>
                                                </div>
                                                {isMin && (
                                                    <Badge
                                                        variant="destructive"
                                                        className="hidden shrink-0 sm:inline-flex"
                                                    >
                                                        menor
                                                    </Badge>
                                                )}
                                                <span
                                                    className={`w-12 shrink-0 text-right text-sm font-bold ${gc(p.group_average)}`}
                                                >
                                                    {formatDecimal(
                                                        p.group_average,
                                                    )}
                                                </span>
                                                {isExp ? (
                                                    <ChevronUp className="h-4 w-4 shrink-0 text-muted-foreground" />
                                                ) : (
                                                    <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground" />
                                                )}
                                            </button>
                                            {isExp && (
                                                <div className="border-t bg-muted/10 px-4 pt-3 pb-4">
                                                    <p className="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                        Distribución de
                                                        calificaciones
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
                                                                        <span className="text-xs font-bold">
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
                </div>
            )}

            {/* ── Por resultado (RA) ── */}
            {activeSection === 'outcomes' && (
                <div className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Promedio por resultado de aprendizaje
                                (transversal)
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                Promedio de ese RA a través de{' '}
                                <strong>todas las programaciones</strong> del
                                espacio · escala 1.3–5.0
                            </p>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={220}>
                                <BarChart
                                    data={[...by_outcome]
                                        .reverse()
                                        .map((o) => ({
                                            name:
                                                o.outcome_code ??
                                                `RA${o.outcome_id}`,
                                            fullName: o.outcome_desc,
                                            promedio: o.group_average,
                                            type: o.type_name ?? '',
                                        }))}
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
                                            `${formatDecimal(Number(v))} — ${ll(Number(v))}`,
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
                                        radius={[4, 4, 0, 0]}
                                    >
                                        {[...by_outcome]
                                            .reverse()
                                            .map((o, i) => (
                                                <Cell
                                                    key={i}
                                                    fill={
                                                        TYPE_COLORS[
                                                            o.type_name ?? ''
                                                        ] ?? TYPE_FALLBACK
                                                    }
                                                    opacity={
                                                        o.outcome_id ===
                                                        minOutcome?.outcome_id
                                                            ? 1
                                                            : 0.65
                                                    }
                                                />
                                            ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    <div className="space-y-2">
                        {by_outcome.map((o) => {
                            const isMin =
                                o.outcome_id === minOutcome?.outcome_id;
                            const typeColor =
                                TYPE_COLORS[o.type_name ?? ''] ?? TYPE_FALLBACK;
                            return (
                                <Card
                                    key={o.outcome_id}
                                    className={`border-l-4 ${isMin ? 'border-amber-300' : ''}`}
                                    style={{
                                        borderLeftColor: isMin
                                            ? undefined
                                            : typeColor,
                                    }}
                                >
                                    <CardContent className="pt-3 pb-3">
                                        <div className="flex items-start gap-3">
                                            <span
                                                className="shrink-0 rounded px-1.5 py-0.5 text-xs font-bold text-white"
                                                style={{
                                                    backgroundColor: typeColor,
                                                }}
                                            >
                                                {o.outcome_code ??
                                                    `RA${o.outcome_id}`}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm leading-snug">
                                                    {o.outcome_desc}
                                                </p>
                                                <div className="mt-1 flex flex-wrap gap-3 text-xs text-muted-foreground">
                                                    <span>{o.type_name}</span>
                                                    <span>
                                                        {o.programming_count}{' '}
                                                        grupo
                                                        {o.programming_count !==
                                                        1
                                                            ? 's'
                                                            : ''}{' '}
                                                        calificado
                                                        {o.programming_count !==
                                                        1
                                                            ? 's'
                                                            : ''}
                                                    </span>
                                                    <span>
                                                        ↑{' '}
                                                        {formatDecimal(
                                                            o.highest,
                                                        )}{' '}
                                                        · ↓{' '}
                                                        {formatDecimal(
                                                            o.lowest,
                                                        )}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="shrink-0 text-right">
                                                <p
                                                    className={`text-xl font-bold ${gc(o.group_average)}`}
                                                >
                                                    {formatDecimal(
                                                        o.group_average,
                                                    )}
                                                </p>
                                                {isMin && (
                                                    <span className="text-xs font-medium text-amber-600">
                                                        ↓ más difícil
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full"
                                                style={{
                                                    width: `${((o.group_average - 1.3) / 3.7) * 100}%`,
                                                    backgroundColor:
                                                        levelColor(ll(o.group_average)) ?? typeColor,
                                                }}
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* ── Por criterio ── */}
            {activeSection === 'criteria' && (
                <div className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Promedio por criterio de evaluación
                                (transversal)
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                Promedio de <strong>todas</strong> las
                                calificaciones de ese criterio en el espacio ·
                                escala 1.3–5.0
                            </p>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={260}>
                                <BarChart
                                    data={by_criterion.map((c) => ({
                                        name: c.criterion_name,
                                        promedio: c.group_average,
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
                                            `${formatDecimal(Number(v))} — ${ll(Number(v))}`,
                                            (p.payload as { type?: string })
                                                .type ?? 'Criterio',
                                        ]}
                                    />
                                    <Bar
                                        dataKey="promedio"
                                        radius={[4, 4, 0, 0]}
                                    >
                                        {by_criterion.map((c, i) => (
                                            <Cell
                                                key={i}
                                                fill={
                                                    TYPE_COLORS[
                                                        c.type_name ?? ''
                                                    ] ?? TYPE_FALLBACK
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

                    {/* Agrupado por tipo */}
                    {Object.entries(
                        by_criterion.reduce<Record<string, CriterionRow[]>>(
                            (acc, c) => {
                                const t = c.type_name ?? 'Otros';
                                if (!acc[t]) acc[t] = [];
                                acc[t].push(c);
                                return acc;
                            },
                            {},
                        ),
                    ).map(([typeName, criteria]) => {
                        const typeColor =
                            TYPE_COLORS[typeName] ?? TYPE_FALLBACK;
                        const typeMin = [...criteria].sort(
                            (a, b) => a.group_average - b.group_average,
                        )[0];
                        return (
                            <Card
                                key={typeName}
                                className="border-t-4"
                                style={{ borderTopColor: typeColor }}
                            >
                                <CardHeader className="pb-2">
                                    <CardTitle
                                        className="text-sm"
                                        style={{ color: typeColor }}
                                    >
                                        {typeName}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        {criteria.map((c) => {
                                            const isMin =
                                                c.criterion_id ===
                                                typeMin?.criterion_id;
                                            return (
                                                <div
                                                    key={c.criterion_id}
                                                    className={`rounded-lg px-3 py-2 ${isMin ? 'bg-amber-50 dark:bg-amber-950/20' : 'bg-muted/40'}`}
                                                >
                                                    <div className="flex items-center justify-between gap-2">
                                                        <p
                                                            className={`text-xs leading-tight ${isMin ? 'font-semibold text-amber-800 dark:text-amber-300' : 'text-muted-foreground'}`}
                                                        >
                                                            {c.criterion_name}
                                                            {isMin && (
                                                                <span className="ml-1 text-amber-500">
                                                                    ↓ menor
                                                                </span>
                                                            )}
                                                        </p>
                                                        <span
                                                            className={`shrink-0 rounded px-1.5 py-0.5 text-xs font-bold ${gbg(c.group_average)}`}
                                                        >
                                                            {formatDecimal(
                                                                c.group_average,
                                                            )}
                                                        </span>
                                                    </div>
                                                    <div className="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                                        <div
                                                            className="h-full rounded-full"
                                                            style={{
                                                                width: `${((c.group_average - 1.3) / 3.7) * 100}%`,
                                                                backgroundColor:
                                                                    typeColor,
                                                            }}
                                                        />
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}

                    {minCriterion && (
                        <Card className="border-amber-200 bg-amber-50/40 dark:border-amber-800 dark:bg-amber-950/10">
                            <CardContent className="pt-5">
                                <div className="flex items-start gap-4">
                                    <Zap className="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
                                    <div>
                                        <p className="text-xs font-semibold tracking-wide text-amber-700 uppercase dark:text-amber-400">
                                            Criterio con menor promedio en este
                                            espacio
                                        </p>
                                        <p className="mt-0.5 text-lg font-bold text-amber-800 dark:text-amber-300">
                                            {minCriterion.criterion_name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Tipo: {minCriterion.type_name} ·
                                            Promedio:{' '}
                                            <strong
                                                className={gc(
                                                    minCriterion.group_average,
                                                )}
                                            >
                                                {formatDecimal(
                                                    minCriterion.group_average,
                                                )}
                                            </strong>{' '}
                                            ({ll(minCriterion.group_average)})
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            )}
        </div>
    );
}
