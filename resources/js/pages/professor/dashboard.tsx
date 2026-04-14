import { Head, Link } from '@inertiajs/react';
import { BookOpen, CheckCircle2, Clock, Users } from 'lucide-react';
import { useMemo, useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ProfessorLayout from '@/layouts/professor/professor-layout';
import { formatDecimal } from '@/lib/utils';
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

type Props = { programmings: ProgrammingCard[] };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/professor/dashboard' },
];

export default function ProfessorDashboard({ programmings }: Props) {
    const periods = useMemo(
        () => [...new Set(programmings.map((p) => p.period))].sort().reverse(),
        [programmings],
    );

    const [selectedPeriod, setSelectedPeriod] = useState<string | null>(
        periods[0] ?? null,
    );

    const filtered = selectedPeriod
        ? programmings.filter((p) => p.period === selectedPeriod)
        : programmings;

    return (
        <ProfessorLayout breadcrumbs={breadcrumbs}>
            <Head title="Mis Programaciones" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <PageHeader
                    title="Mis Programaciones"
                    description="Espacios académicos asignados a tu perfil"
                />

                {/* Filtro por período */}
                {periods.length > 1 && (
                    <div className="flex flex-wrap gap-2">
                        <Button
                            size="sm"
                            variant={!selectedPeriod ? 'secondary' : 'outline'}
                            onClick={() => setSelectedPeriod(null)}
                        >
                            Todos los períodos
                        </Button>
                        {periods.map((period) => (
                            <Button
                                key={period}
                                size="sm"
                                variant={selectedPeriod === period ? 'secondary' : 'outline'}
                                onClick={() => setSelectedPeriod(period)}
                            >
                                {period}
                            </Button>
                        ))}
                    </div>
                )}

                {filtered.length === 0 ? (
                    <EmptyState
                        title="Sin programaciones activas"
                        description="No tienes programaciones activas asignadas en este momento."
                        icon={BookOpen}
                    />
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {filtered.map((p) => (
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
                                                <span className="flex items-center gap-1 text-muted-foreground">
                                                    {p.grading_percentage >= 100
                                                        ? <CheckCircle2 className="h-3 w-3 text-green-600" />
                                                        : <Clock className="h-3 w-3" />}
                                                    Calificaciones
                                                </span>
                                                <span className={`font-medium ${p.grading_percentage >= 100 ? 'text-green-600' : ''}`}>
                                                    {formatDecimal(p.grading_percentage)}%
                                                </span>
                                            </div>
                                            <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className={`h-full rounded-full transition-all ${p.grading_percentage >= 100 ? 'bg-green-500' : 'bg-primary'}`}
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
