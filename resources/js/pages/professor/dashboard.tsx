import { Head, Link } from '@inertiajs/react';
import { BookOpen, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { formatDecimal } from '@/lib/utils';
import ProfessorLayout from '@/layouts/professor/professor-layout';
import type { BreadcrumbItem } from '@/types';

type ProgrammingCard = {
    id: number;
    period: string;
    group: string | null;
    academic_space: { id: number; name: string; code: string };
    modality: { id: number; name: string };
    enrolled_count: number;
    grading_percentage: number;
};

type Props = {
    programmings: ProgrammingCard[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/professor/dashboard' },
];

export default function ProfessorDashboard({ programmings }: Props) {
    return (
        <ProfessorLayout breadcrumbs={breadcrumbs}>
            <Head title="Mis Programaciones" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Mis Programaciones"
                    description="Espacios académicos asignados a tu perfil"
                />

                {programmings.length === 0 ? (
                    <EmptyState
                        title="Sin programaciones activas"
                        description="No tienes programaciones activas asignadas en este momento."
                        icon={BookOpen}
                    />
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {programmings.map((p) => (
                            <Link
                                key={p.id}
                                href={`/professor/programmings/${p.id}/grading`}
                                prefetch
                            >
                                <Card className="h-full cursor-pointer transition-shadow hover:shadow-md">
                                    <CardHeader className="pb-2">
                                        <div className="flex items-start justify-between gap-2">
                                            <CardTitle className="text-base leading-tight">
                                                {p.academic_space.name}
                                            </CardTitle>
                                            <Badge variant="outline" className="shrink-0 text-xs">
                                                {p.academic_space.code}
                                            </Badge>
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {p.period}{p.group ? ` · Grupo ${p.group}` : ''} · {p.modality.name}
                                        </p>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Users className="h-4 w-4" />
                                            <span>{p.enrolled_count} estudiantes inscritos</span>
                                        </div>
                                        <div>
                                            <div className="mb-1 flex items-center justify-between text-xs">
                                                <span className="text-muted-foreground">Calificaciones</span>
                                                <span className="font-medium">
                                                    {formatDecimal(p.grading_percentage)}%
                                                </span>
                                            </div>
                                            <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className="h-full rounded-full bg-primary transition-all"
                                                    style={{ width: `${p.grading_percentage}%` }}
                                                />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </ProfessorLayout>
    );
}
