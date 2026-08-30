<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AdminAcademicSpaceStatisticsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicSpaceRequest;
use App\Http\Requests\Admin\UpdateAcademicSpaceRequest;
use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\PerformanceLevel;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AcademicSpaceController extends Controller
{
    public function index(): Response
    {
        $facultyId = request('faculty_id');
        $programId = request('program_id');
        $nucleusId = request('problematic_nucleus_id');
        $competencyId = request('competency_id');

        $academicSpaces = AcademicSpace::query()
            ->with('competency.problematicNucleus.program.faculty')
            ->when(request('search'), fn ($q, $search) => $q->where(
                fn ($sub) => $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
            ))
            ->when($competencyId, fn ($q) => $q->where('competency_id', $competencyId))
            ->when($nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $faculties = Faculty::query()->active()->orderBy('name')->get(['id', 'name']);

        $programs = Program::query()->active()
            ->when($facultyId, fn ($q) => $q->where('faculty_id', $facultyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $nuclei = ProblematicNucleus::query()->active()
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->when($facultyId && ! $programId, fn ($q) => $q->whereHas('program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->get(['id', 'name']);

        $competencies = Competency::query()->active()
            ->when($nucleusId, fn ($q) => $q->where('problematic_nucleus_id', $nucleusId))
            ->when($programId && ! $nucleusId, fn ($q) => $q->whereHas('problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId, fn ($q) => $q->whereHas('problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return Inertia::render('admin/academic-spaces/index', [
            'academicSpaces' => $academicSpaces,
            'faculties' => $faculties,
            'programs' => $programs,
            'nuclei' => $nuclei,
            'competencies' => $competencies,
            'filters' => request()->only(['search', 'faculty_id', 'program_id', 'problematic_nucleus_id', 'competency_id']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/academic-spaces/create', [
            'competencies' => Competency::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreAcademicSpaceRequest $request): RedirectResponse
    {
        AcademicSpace::create($request->validated());

        return to_route('admin.academic-spaces.index')->with('success', 'Espacio académico creado exitosamente.');
    }

    public function show(AcademicSpace $academicSpace): Response
    {
        $academicSpace->load([
            'competency',
            'microcurricularLearningOutcomes.type',
            'topics' => fn ($q) => $q->orderBy('order'),
            'programmings.professor',
            'programmings.academicPeriod',
        ]);

        // ── Statistics ────────────────────────────────────────────────────────
        $performanceLevels = PerformanceLevel::orderBy('order')->get(['id', 'name', 'order']);

        $programmingIds = $academicSpace->programmings->pluck('id');

        $statistics = null;

        if ($programmingIds->isNotEmpty()) {
            $grades = Grade::query()
                ->whereHas('enrollment', fn ($q) => $q->whereIn('programming_id', $programmingIds)->where('is_active', true))
                ->with([
                    'enrollment.programming.academicPeriod',
                    'enrollment.programming.professor',
                    'microcurricularLearningOutcome.type',
                    'evaluationCriterion.outcomeType',
                    'performanceLevel',
                ])
                ->get();

            if ($grades->isNotEmpty()) {
                // ── By programming ────────────────────────────────────────────
                $byProgramming = $grades
                    ->groupBy(fn ($g) => $g->enrollment->programming_id)
                    ->map(function ($pg) use ($performanceLevels) {
                        $prog = $pg->first()->enrollment->programming;

                        $gradesByStudent = $pg->groupBy('enrollment_id')
                            ->map(fn ($sg) => round(
                                $sg->avg(fn ($g) => PerformanceLevel::gradeForOrder($g->performanceLevel->order)),
                                2
                            ))->values();

                        $groupAverage = $gradesByStudent->isNotEmpty()
                            ? round($gradesByStudent->avg(), 2) : 0.0;

                        $totalGrades = $pg->count();
                        $distribution = $performanceLevels->map(fn ($l) => [
                            'level_id' => $l->id,
                            'level_name' => $l->name,
                            'count' => $pg->where('performance_level_id', $l->id)->count(),
                            'percentage' => $totalGrades > 0
                                ? round(($pg->where('performance_level_id', $l->id)->count() / $totalGrades) * 100, 1)
                                : 0.0,
                        ])->values();

                        return [
                            'programming_id' => $prog->id,
                            'period' => $prog->academicPeriod?->name ?? '—',
                            'group' => $prog->group,
                            'professor' => $prog->professor
                                ? ['first_name' => $prog->professor->first_name, 'last_name' => $prog->professor->last_name]
                                : null,
                            'student_count' => $gradesByStudent->count(),
                            'group_average' => $groupAverage,
                            'highest' => $gradesByStudent->max() ?? 0.0,
                            'lowest' => $gradesByStudent->min() ?? 0.0,
                            'distribution' => $distribution,
                        ];
                    })
                    ->sortByDesc('group_average')
                    ->values();

                // ── By outcome ────────────────────────────────────────────────
                $byOutcome = $grades
                    ->groupBy('microcurricular_learning_outcome_id')
                    ->map(function ($og) use ($performanceLevels) {
                        $outcome = $og->first()->microcurricularLearningOutcome;

                        $gradesByStudent = $og->groupBy('enrollment_id')
                            ->map(fn ($sg) => round(
                                $sg->avg(fn ($g) => PerformanceLevel::gradeForOrder($g->performanceLevel->order)),
                                2
                            ))->values();

                        $groupAvg = $gradesByStudent->isNotEmpty()
                            ? round($gradesByStudent->avg(), 2) : 0.0;

                        $totalGrades = $og->count();
                        $distribution = $performanceLevels->map(fn ($l) => [
                            'level_id' => $l->id,
                            'level_name' => $l->name,
                            'count' => $og->where('performance_level_id', $l->id)->count(),
                            'percentage' => $totalGrades > 0
                                ? round(($og->where('performance_level_id', $l->id)->count() / $totalGrades) * 100, 1)
                                : 0.0,
                        ])->values();

                        return [
                            'outcome_id' => $outcome->id,
                            'outcome_code' => $outcome->code,
                            'outcome_desc' => $outcome->description,
                            'type_id' => $outcome->type_id,
                            'type_name' => $outcome->type?->name,
                            'group_average' => $groupAvg,
                            'highest' => $gradesByStudent->max() ?? 0.0,
                            'lowest' => $gradesByStudent->min() ?? 0.0,
                            'distribution' => $distribution,
                            'programming_count' => $og->unique(fn ($g) => $g->enrollment->programming_id)->count(),
                        ];
                    })
                    ->sortByDesc('group_average')
                    ->values();

                // ── By criterion ──────────────────────────────────────────────
                $byCriterion = $grades
                    ->groupBy('evaluation_criterion_id')
                    ->map(function ($cg) {
                        $criterion = $cg->first()->evaluationCriterion;

                        return [
                            'criterion_id' => $criterion->id,
                            'criterion_name' => $criterion->name,
                            'type_id' => $criterion->microcurricular_learning_outcome_type_id,
                            'type_name' => $criterion->outcomeType?->name,
                            'group_average' => round(
                                $cg->avg(fn ($g) => PerformanceLevel::gradeForOrder($g->performanceLevel->order)),
                                2
                            ),
                        ];
                    })
                    ->sortByDesc('group_average')
                    ->values();

                // ── Summary ───────────────────────────────────────────────────
                // Averaged per student, not per programming: a mean of group
                // means weights a small group the same as a large one.
                $studentAverages = $grades
                    ->groupBy('enrollment_id')
                    ->map(fn ($sg) => $sg->avg(
                        fn ($g) => PerformanceLevel::gradeForOrder($g->performanceLevel->order)
                    ));
                $globalAverage = $studentAverages->isNotEmpty()
                    ? round($studentAverages->avg(), 2) : 0.0;

                $totalGrades = $grades->count();
                $globalDistribution = $performanceLevels->map(function ($l) use ($grades, $totalGrades) {
                    $count = $grades->where('performance_level_id', $l->id)->count();

                    return [
                        'level_id' => $l->id,
                        'level_name' => $l->name,
                        'count' => $count,
                        'percentage' => $totalGrades > 0 ? round(($count / $totalGrades) * 100, 1) : 0.0,
                    ];
                })->values();

                $trendByPeriod = $byProgramming
                    ->groupBy('period')
                    ->map(fn ($items) => round($items->avg('group_average'), 2))
                    ->map(fn ($avg, $period) => ['period' => $period, 'average' => $avg])
                    ->values()
                    ->sortBy('period')
                    ->values();

                $statistics = [
                    'summary' => [
                        'global_average' => $globalAverage,
                        'total_programmings' => $byProgramming->count(),
                        'total_grade_records' => $totalGrades,
                        'distribution' => $globalDistribution,
                        'trend_by_period' => $trendByPeriod,
                    ],
                    'by_programming' => $byProgramming,
                    'by_outcome' => $byOutcome,
                    'by_criterion' => $byCriterion,
                ];
            }
        }

        return Inertia::render('admin/academic-spaces/show', [
            'academicSpace' => $academicSpace,
            'statistics' => $statistics,
        ]);
    }

    public function downloadStatistics(AcademicSpace $academicSpace): BinaryFileResponse
    {
        $academicSpace->load(['competency']);

        $performanceLevels = PerformanceLevel::orderBy('order')->get(['id', 'name', 'order']);
        $programmingIds = $academicSpace->programmings()->pluck('id');

        abort_if($programmingIds->isEmpty(), 422, 'Este espacio académico no tiene programaciones con datos.');

        $grades = Grade::query()
            ->whereHas('enrollment', fn ($q) => $q->whereIn('programming_id', $programmingIds)->where('is_active', true))
            ->with([
                'enrollment.programming.academicPeriod',
                'enrollment.programming.professor',
                'microcurricularLearningOutcome.type',
                'evaluationCriterion.outcomeType',
                'performanceLevel',
            ])
            ->get();

        abort_if($grades->isEmpty(), 422, 'Este espacio académico no tiene calificaciones registradas.');

        $byProgramming = $grades
            ->groupBy(fn ($g) => $g->enrollment->programming_id)
            ->map(function ($pg) use ($performanceLevels) {
                $prog = $pg->first()->enrollment->programming;

                $gradesByStudent = $pg->groupBy('enrollment_id')
                    ->map(fn ($sg) => round(
                        $sg->avg(fn ($g) => PerformanceLevel::gradeForOrder($g->performanceLevel->order)),
                        2
                    ))->values();

                $groupAverage = $gradesByStudent->isNotEmpty()
                    ? round($gradesByStudent->avg(), 2) : 0.0;

                $totalGrades = $pg->count();
                $distribution = $performanceLevels->map(fn ($l) => [
                    'level_id' => $l->id,
                    'level_name' => $l->name,
                    'count' => $pg->where('performance_level_id', $l->id)->count(),
                    'percentage' => $totalGrades > 0
                        ? round(($pg->where('performance_level_id', $l->id)->count() / $totalGrades) * 100, 1)
                        : 0.0,
                ])->values();

                return [
                    'programming_id' => $prog->id,
                    'period' => $prog->academicPeriod?->name ?? '—',
                    'group' => $prog->group,
                    'professor' => $prog->professor
                        ? ['first_name' => $prog->professor->first_name, 'last_name' => $prog->professor->last_name]
                        : null,
                    'student_count' => $gradesByStudent->count(),
                    'group_average' => $groupAverage,
                    'highest' => $gradesByStudent->max() ?? 0.0,
                    'lowest' => $gradesByStudent->min() ?? 0.0,
                    'distribution' => $distribution,
                ];
            })
            ->sortByDesc('group_average')
            ->values();

        $byOutcome = $grades
            ->groupBy('microcurricular_learning_outcome_id')
            ->map(function ($og) use ($performanceLevels) {
                $outcome = $og->first()->microcurricularLearningOutcome;

                $gradesByStudent = $og->groupBy('enrollment_id')
                    ->map(fn ($sg) => round(
                        $sg->avg(fn ($g) => PerformanceLevel::gradeForOrder($g->performanceLevel->order)),
                        2
                    ))->values();

                $groupAvg = $gradesByStudent->isNotEmpty()
                    ? round($gradesByStudent->avg(), 2) : 0.0;

                $totalGrades = $og->count();
                $distribution = $performanceLevels->map(fn ($l) => [
                    'level_id' => $l->id,
                    'level_name' => $l->name,
                    'count' => $og->where('performance_level_id', $l->id)->count(),
                    'percentage' => $totalGrades > 0
                        ? round(($og->where('performance_level_id', $l->id)->count() / $totalGrades) * 100, 1)
                        : 0.0,
                ])->values();

                return [
                    'outcome_id' => $outcome->id,
                    'outcome_code' => $outcome->code,
                    'outcome_desc' => $outcome->description,
                    'type_id' => $outcome->type_id,
                    'type_name' => $outcome->type?->name,
                    'group_average' => $groupAvg,
                    'highest' => $gradesByStudent->max() ?? 0.0,
                    'lowest' => $gradesByStudent->min() ?? 0.0,
                    'distribution' => $distribution,
                    'programming_count' => $og->unique(fn ($g) => $g->enrollment->programming_id)->count(),
                ];
            })
            ->sortByDesc('group_average')
            ->values();

        $byCriterion = $grades
            ->groupBy('evaluation_criterion_id')
            ->map(function ($cg) {
                $criterion = $cg->first()->evaluationCriterion;

                return [
                    'criterion_id' => $criterion->id,
                    'criterion_name' => $criterion->name,
                    'type_id' => $criterion->microcurricular_learning_outcome_type_id,
                    'type_name' => $criterion->outcomeType?->name,
                    'group_average' => round(
                        $cg->avg(fn ($g) => PerformanceLevel::gradeForOrder($g->performanceLevel->order)),
                        2
                    ),
                ];
            })
            ->sortByDesc('group_average')
            ->values();

        // Averaged per student, not per programming — see show().
        $studentAverages = $grades
            ->groupBy('enrollment_id')
            ->map(fn ($sg) => $sg->avg(
                fn ($g) => PerformanceLevel::gradeForOrder($g->performanceLevel->order)
            ));
        $globalAverage = $studentAverages->isNotEmpty()
            ? round($studentAverages->avg(), 2) : 0.0;

        $totalGrades = $grades->count();
        $globalDistribution = $performanceLevels->map(function ($l) use ($grades, $totalGrades) {
            $count = $grades->where('performance_level_id', $l->id)->count();

            return [
                'level_id' => $l->id,
                'level_name' => $l->name,
                'count' => $count,
                'percentage' => $totalGrades > 0 ? round(($count / $totalGrades) * 100, 1) : 0.0,
            ];
        })->values();

        $trendByPeriod = $byProgramming
            ->groupBy('period')
            ->map(fn ($items) => round($items->avg('group_average'), 2))
            ->map(fn ($avg, $period) => ['period' => $period, 'average' => $avg])
            ->values()
            ->sortBy('period')
            ->values();

        $statistics = [
            'summary' => [
                'global_average' => $globalAverage,
                'total_programmings' => $byProgramming->count(),
                'total_grade_records' => $totalGrades,
                'distribution' => $globalDistribution->toArray(),
                'trend_by_period' => $trendByPeriod->toArray(),
            ],
            'by_programming' => $byProgramming->toArray(),
            'by_outcome' => $byOutcome->toArray(),
            'by_criterion' => $byCriterion->toArray(),
        ];

        $fileName = 'estadisticas_espacio_'.$academicSpace->id.'_'.now()->format('Ymd').'.xlsx';

        return Excel::download(
            new AdminAcademicSpaceStatisticsExport($academicSpace, $statistics),
            $fileName
        );
    }

    public function edit(AcademicSpace $academicSpace): Response
    {
        return Inertia::render('admin/academic-spaces/edit', [
            'academicSpace' => $academicSpace->load('competency'),
            'competencies' => Competency::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(UpdateAcademicSpaceRequest $request, AcademicSpace $academicSpace): RedirectResponse
    {
        $academicSpace->update($request->validated());

        return to_route('admin.academic-spaces.index')->with('success', 'Espacio académico actualizado exitosamente.');
    }

    public function toggleStatus(AcademicSpace $academicSpace): RedirectResponse
    {
        $academicSpace->update(['is_active' => ! $academicSpace->is_active]);

        $status = $academicSpace->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Espacio académico {$status} exitosamente.");
    }
}
