<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreActivityRequest;
use App\Http\Requests\Admin\UpdateActivityRequest;
use App\Models\AcademicSpace;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(): Response
    {
        $facultyId = request('faculty_id');
        $programId = request('program_id');
        $nucleusId = request('problematic_nucleus_id');
        $competencyId = request('competency_id');
        $spaceId = request('academic_space_id');
        $topicId = request('topic_id');

        $activities = Activity::query()
            ->with(['topic.academicSpace.competency.problematicNucleus.program.faculty', 'activityType'])
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($topicId, fn ($q) => $q->where('topic_id', $topicId))
            ->when($spaceId && ! $topicId, fn ($q) => $q->whereHas('topic', fn ($tq) => $tq->where('academic_space_id', $spaceId)))
            ->when($competencyId && ! $spaceId && ! $topicId, fn ($q) => $q->whereHas('topic.academicSpace', fn ($sq) => $sq->where('competency_id', $competencyId)))
            ->when($nucleusId && ! $competencyId && ! $spaceId && ! $topicId, fn ($q) => $q->whereHas('topic.academicSpace.competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId && ! $spaceId && ! $topicId, fn ($q) => $q->whereHas('topic.academicSpace.competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId && ! $spaceId && ! $topicId, fn ($q) => $q->whereHas('topic.academicSpace.competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('topic_id')
            ->orderBy('order')
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
            ->get(['id', 'name']);

        $academicSpaces = AcademicSpace::query()->active()
            ->when($competencyId, fn ($q) => $q->where('competency_id', $competencyId))
            ->when($nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId, fn ($q) => $q->whereHas('competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->get(['id', 'name']);

        $topics = Topic::query()->active()
            ->when($spaceId, fn ($q) => $q->where('academic_space_id', $spaceId))
            ->when($competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace', fn ($sq) => $sq->where('competency_id', $competencyId)))
            ->when($nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/activities/index', [
            'activities' => $activities,
            'faculties' => $faculties,
            'programs' => $programs,
            'nuclei' => $nuclei,
            'competencies' => $competencies,
            'academicSpaces' => $academicSpaces,
            'topics' => $topics,
            'filters' => request()->only('search', 'faculty_id', 'program_id', 'problematic_nucleus_id', 'competency_id', 'academic_space_id', 'topic_id'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/activities/create', [
            'topics' => Topic::query()->active()->orderBy('name')->get(['id', 'name']),
            'activityTypes' => ActivityType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        Activity::create($request->validated());

        return to_route('admin.activities.index')->with('success', 'Actividad creada exitosamente.');
    }

    public function edit(Activity $activity): Response
    {
        return Inertia::render('admin/activities/edit', [
            'activity' => $activity->load(['topic', 'activityType']),
            'topics' => Topic::query()->active()->orderBy('name')->get(['id', 'name']),
            'activityTypes' => ActivityType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $activity->update($request->validated());

        return to_route('admin.activities.index')->with('success', 'Actividad actualizada exitosamente.');
    }

    public function toggleStatus(Activity $activity): RedirectResponse
    {
        $activity->update(['is_active' => ! $activity->is_active]);

        $status = $activity->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Actividad {$status} exitosamente.");
    }
}
