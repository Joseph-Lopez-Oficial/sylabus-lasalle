<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AdminMicrocurricularOutcomeExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMicrocurricularLearningOutcomeRequest;
use App\Http\Requests\Admin\UpdateMicrocurricularLearningOutcomeRequest;
use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\MesocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcomeType;
use App\Models\PerformanceLevel;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MicrocurricularLearningOutcomeController extends Controller
{
    public function index(): Response
    {
        $facultyId = request('faculty_id');
        $programId = request('program_id');
        $nucleusId = request('problematic_nucleus_id');
        $competencyId = request('competency_id');
        $spaceId = request('academic_space_id');

        $outcomes = MicrocurricularLearningOutcome::query()
            ->with(['academicSpace.competency.problematicNucleus.program.faculty', 'type', 'mesocurricularLearningOutcome'])
            ->when(request('search'), fn ($q, $search) => $q->where('description', 'like', "%{$search}%"))
            ->when($spaceId, fn ($q) => $q->where('academic_space_id', $spaceId))
            ->when($competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace', fn ($sq) => $sq->where('competency_id', $competencyId)))
            ->when($nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('id')
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

        $academicSpaces = AcademicSpace::query()->active()
            ->when($competencyId, fn ($q) => $q->where('competency_id', $competencyId))
            ->when($nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return Inertia::render('admin/microcurricular-outcomes/index', [
            'outcomes' => $outcomes,
            'faculties' => $faculties,
            'programs' => $programs,
            'nuclei' => $nuclei,
            'competencies' => $competencies,
            'academicSpaces' => $academicSpaces,
            'filters' => request()->only(['search', 'faculty_id', 'program_id', 'problematic_nucleus_id', 'competency_id', 'academic_space_id']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/microcurricular-outcomes/create', [
            'academicSpaces' => AcademicSpace::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'types' => MicrocurricularLearningOutcomeType::query()->orderBy('name')->get(['id', 'name']),
            'mesocurricularOutcomes' => MesocurricularLearningOutcome::query()->active()->orderBy('id')->get(['id', 'code', 'description']),
        ]);
    }

    public function store(StoreMicrocurricularLearningOutcomeRequest $request): RedirectResponse
    {
        MicrocurricularLearningOutcome::create($request->validated());

        return to_route('admin.microcurricular-outcomes.index')->with('success', 'Resultado microcurricular creado exitosamente.');
    }

    public function edit(MicrocurricularLearningOutcome $microcurricularOutcome): Response
    {
        return Inertia::render('admin/microcurricular-outcomes/edit', [
            'outcome' => $microcurricularOutcome->load(['academicSpace', 'type', 'mesocurricularLearningOutcome']),
            'academicSpaces' => AcademicSpace::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'types' => MicrocurricularLearningOutcomeType::query()->orderBy('name')->get(['id', 'name']),
            'mesocurricularOutcomes' => MesocurricularLearningOutcome::query()->active()->orderBy('id')->get(['id', 'code', 'description']),
        ]);
    }

    public function update(UpdateMicrocurricularLearningOutcomeRequest $request, MicrocurricularLearningOutcome $microcurricularOutcome): RedirectResponse
    {
        $microcurricularOutcome->update($request->validated());

        return to_route('admin.microcurricular-outcomes.index')->with('success', 'Resultado microcurricular actualizado exitosamente.');
    }

    public function toggleStatus(MicrocurricularLearningOutcome $microcurricularOutcome): RedirectResponse
    {
        $microcurricularOutcome->update(['is_active' => ! $microcurricularOutcome->is_active]);

        $status = $microcurricularOutcome->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Resultado microcurricular {$status} exitosamente.");
    }

    private function buildOutcomeStats(MicrocurricularLearningOutcome $microcurricularOutcome): array
    {
        $performanceLevels = PerformanceLevel::orderBy('order')->get(['id', 'name', 'order']);
        $orderToGrade = [1 => 1.3, 2 => 2.5, 3 => 3.8, 4 => 5.0];

        $grades = Grade::query()
            ->where('microcurricular_learning_outcome_id', $microcurricularOutcome->id)
            ->with([
                'enrollment.programming.academicPeriod',
                'enrollment.programming.professor',
                'enrollment.programming.academicSpace',
                'performanceLevel',
            ])
            ->get();

        $byProgramming = $grades->groupBy(fn ($g) => $g->enrollment->programming_id)
            ->map(function ($progGrades) use ($performanceLevels, $orderToGrade) {
                $programming = $progGrades->first()->enrollment->programming;

                $gradesByStudent = $progGrades
                    ->groupBy('enrollment_id')
                    ->map(fn ($sg) => round(
                        $sg->avg(fn ($g) => $orderToGrade[$g->performanceLevel->order] ?? $g->performanceLevel->order),
                        2
                    ))
                    ->values();

                $groupAverage = $gradesByStudent->isNotEmpty()
                    ? round($gradesByStudent->avg(), 2) : 0.0;

                $totalGrades = $progGrades->count();
                $distribution = $performanceLevels->map(function ($level) use ($progGrades, $totalGrades) {
                    $count = $progGrades->where('performance_level_id', $level->id)->count();

                    return [
                        'level_id' => $level->id,
                        'level_name' => $level->name,
                        'count' => $count,
                        'percentage' => $totalGrades > 0 ? round(($count / $totalGrades) * 100, 1) : 0.0,
                    ];
                })->values();

                return [
                    'programming_id' => $programming->id,
                    'period' => $programming->academicPeriod?->name ?? '—',
                    'group' => $programming->group,
                    'academic_space' => $programming->academicSpace?->only(['id', 'name', 'code']),
                    'professor' => $programming->professor ? [
                        'first_name' => $programming->professor->first_name,
                        'last_name' => $programming->professor->last_name,
                    ] : null,
                    'student_count' => $gradesByStudent->count(),
                    'group_average' => $groupAverage,
                    'highest' => $gradesByStudent->max() ?? 0.0,
                    'lowest' => $gradesByStudent->min() ?? 0.0,
                    'distribution' => $distribution,
                ];
            })
            ->values()
            ->sortByDesc('group_average')
            ->values();

        $allStudentAverages = $byProgramming->pluck('group_average');
        $globalAverage = $allStudentAverages->isNotEmpty()
            ? round($allStudentAverages->avg(), 2) : 0.0;

        $totalGrades = $grades->count();
        $globalDistribution = $performanceLevels->map(function ($level) use ($grades, $totalGrades) {
            $count = $grades->where('performance_level_id', $level->id)->count();

            return [
                'level_id' => $level->id,
                'level_name' => $level->name,
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

        return [
            'summary' => [
                'global_average' => $globalAverage,
                'total_programmings' => $byProgramming->count(),
                'total_grade_records' => $totalGrades,
                'distribution' => $globalDistribution->toArray(),
                'trend_by_period' => $trendByPeriod->toArray(),
            ],
            'by_programming' => $byProgramming->toArray(),
        ];
    }

    public function show(MicrocurricularLearningOutcome $microcurricularOutcome): Response
    {
        $microcurricularOutcome->load([
            'type',
            'academicSpace.competency.problematicNucleus.program.faculty',
        ]);

        $stats = $this->buildOutcomeStats($microcurricularOutcome);

        return Inertia::render('admin/microcurricular-outcomes/show', [
            'outcome' => [
                'id' => $microcurricularOutcome->id,
                'code' => $microcurricularOutcome->code,
                'description' => $microcurricularOutcome->description,
                'is_active' => $microcurricularOutcome->is_active,
                'type' => $microcurricularOutcome->type?->only(['id', 'name']),
                'academic_space' => $microcurricularOutcome->academicSpace ? [
                    'id' => $microcurricularOutcome->academicSpace->id,
                    'name' => $microcurricularOutcome->academicSpace->name,
                    'code' => $microcurricularOutcome->academicSpace->code,
                    'competency' => $microcurricularOutcome->academicSpace->competency ? [
                        'name' => $microcurricularOutcome->academicSpace->competency->name,
                        'problematic_nucleus' => $microcurricularOutcome->academicSpace->competency->problematicNucleus ? [
                            'name' => $microcurricularOutcome->academicSpace->competency->problematicNucleus->name,
                            'program' => $microcurricularOutcome->academicSpace->competency->problematicNucleus->program ? [
                                'name' => $microcurricularOutcome->academicSpace->competency->problematicNucleus->program->name,
                                'faculty' => $microcurricularOutcome->academicSpace->competency->problematicNucleus->program->faculty?->only(['name']),
                            ] : null,
                        ] : null,
                    ] : null,
                ] : null,
            ],
            'summary' => $stats['summary'],
            'by_programming' => $stats['by_programming'],
        ]);
    }

    public function downloadReport(MicrocurricularLearningOutcome $microcurricularOutcome): BinaryFileResponse
    {
        $microcurricularOutcome->load([
            'type',
            'academicSpace.competency.problematicNucleus.program.faculty',
        ]);

        $stats = $this->buildOutcomeStats($microcurricularOutcome);

        $fileName = 'reporte_ra_'.$microcurricularOutcome->id.'_'.now()->format('Ymd').'.xlsx';

        return Excel::download(
            new AdminMicrocurricularOutcomeExport(
                $microcurricularOutcome,
                $stats['summary'],
                $stats['by_programming'],
            ),
            $fileName
        );
    }
}
