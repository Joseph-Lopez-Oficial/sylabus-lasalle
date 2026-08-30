import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as ProgrammingController from '@/actions/App/Http/Controllers/Admin/ProgrammingController';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/admin-layout';
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
    professor: { id: number; first_name: string; last_name: string } | null;
    outcomes: Outcome[];
};

const QUESTIONS = [
    {
        field: 'outcome_performance' as const,
        label: 'Desempeño del grupo con relación al Resultado de Aprendizaje',
    },
    {
        field: 'academic_space_performance' as const,
        label: 'Desempeño del grupo con relación al espacio académico',
    },
    {
        field: 'improvement_proposals' as const,
        label: 'Análisis y propuestas de mejora',
    },
];

export default function ProgrammingAnalysis({
    programming,
    academicSpace,
    academicPeriod,
    professor,
    outcomes,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Programaciones',
            href: ProgrammingController.index.url(),
        },
        {
            title: 'Análisis del espacio académico',
            href: `/admin/programmings/${programming.id}/analysis`,
        },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Análisis — ${academicSpace?.name ?? ''}`} />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Análisis del espacio académico"
                    description={`${academicSpace?.name ?? ''}${academicPeriod ? ` · ${academicPeriod.name}` : ''}${programming.group ? ` · Grupo ${programming.group}` : ''}${professor ? ` · ${professor.first_name} ${professor.last_name}` : ''}`}
                >
                    <Button variant="outline" asChild>
                        <Link href={ProgrammingController.index.url()}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </PageHeader>

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
                                    <Badge variant="secondary" className="shrink-0">
                                        {outcome.average === null
                                            ? 'Sin calificar'
                                            : `Promedio ${formatDecimal(outcome.average)}`}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {QUESTIONS.map((question) => {
                                    const text =
                                        outcome.analysis?.[question.field];

                                    return (
                                        <div key={question.field}>
                                            <p className="text-sm font-medium">
                                                {question.label}
                                            </p>
                                            <p className="mt-1 text-sm whitespace-pre-line text-muted-foreground">
                                                {text?.trim() ? (
                                                    text
                                                ) : (
                                                    <span className="italic">
                                                        El profesor aún no ha
                                                        escrito esta respuesta
                                                    </span>
                                                )}
                                            </p>
                                        </div>
                                    );
                                })}
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>
        </AdminLayout>
    );
}
