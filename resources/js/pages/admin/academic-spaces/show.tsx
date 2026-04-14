import { Head, Link } from '@inertiajs/react';
import {
    BookOpen,
    ChevronDown,
    ChevronRight,
    Pencil,
    Plus,
} from 'lucide-react';
import { useState } from 'react';
import * as SpaceController from '@/actions/App/Http/Controllers/Admin/AcademicSpaceController';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin/admin-layout';
import type {
    AcademicSpace,
    BreadcrumbItem,
    MicrocurricularLearningOutcome,
    MicrocurricularLearningOutcomeType,
    Programming,
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

type Props = { academicSpace: AcademicSpaceWithRelations };

export default function AcademicSpacesShow({ academicSpace }: Props) {
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
                                                {p.period}
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
                </Tabs>
            </div>
        </AdminLayout>
    );
}
