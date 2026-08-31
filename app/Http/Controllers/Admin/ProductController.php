<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesListings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\AcademicSpace;
use App\Models\Activity;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\ProblematicNucleus;
use App\Models\Product;
use App\Models\Program;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    use PaginatesListings;

    public function index(): Response
    {
        $facultyId = request('faculty_id');
        $programId = request('program_id');
        $nucleusId = request('problematic_nucleus_id');
        $competencyId = request('competency_id');
        $spaceId = request('academic_space_id');
        $topicId = request('topic_id');
        $activityId = request('activity_id');

        $products = Product::query()
            ->with('activity.topic.academicSpace.competency.problematicNucleus.program.faculty')
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($activityId, fn ($q) => $q->where('activity_id', $activityId))
            ->when($topicId && ! $activityId, fn ($q) => $q->whereHas('activity', fn ($aq) => $aq->where('topic_id', $topicId)))
            ->when($spaceId && ! $topicId && ! $activityId, fn ($q) => $q->whereHas('activity.topic', fn ($tq) => $tq->where('academic_space_id', $spaceId)))
            ->when($competencyId && ! $spaceId && ! $topicId && ! $activityId, fn ($q) => $q->whereHas('activity.topic.academicSpace', fn ($sq) => $sq->where('competency_id', $competencyId)))
            ->when($nucleusId && ! $competencyId && ! $spaceId && ! $topicId && ! $activityId, fn ($q) => $q->whereHas('activity.topic.academicSpace.competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId && ! $spaceId && ! $topicId && ! $activityId, fn ($q) => $q->whereHas('activity.topic.academicSpace.competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId && ! $spaceId && ! $topicId && ! $activityId, fn ($q) => $q->whereHas('activity.topic.academicSpace.competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('activity_id')
            ->orderBy('order')
            ->paginate($this->perPage())
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

        $topics = Topic::query()->active()
            ->when($spaceId, fn ($q) => $q->where('academic_space_id', $spaceId))
            ->when($competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace', fn ($sq) => $sq->where('competency_id', $competencyId)))
            ->when($nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId && ! $spaceId, fn ($q) => $q->whereHas('academicSpace.competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->get(['id', 'name']);

        $activities = Activity::query()->active()
            ->when($topicId, fn ($q) => $q->where('topic_id', $topicId))
            ->when($spaceId && ! $topicId, fn ($q) => $q->whereHas('topic', fn ($tq) => $tq->where('academic_space_id', $spaceId)))
            ->when($competencyId && ! $spaceId && ! $topicId, fn ($q) => $q->whereHas('topic.academicSpace', fn ($sq) => $sq->where('competency_id', $competencyId)))
            ->when($nucleusId && ! $competencyId && ! $spaceId && ! $topicId, fn ($q) => $q->whereHas('topic.academicSpace.competency', fn ($cq) => $cq->where('problematic_nucleus_id', $nucleusId)))
            ->when($programId && ! $nucleusId && ! $competencyId && ! $spaceId && ! $topicId, fn ($q) => $q->whereHas('topic.academicSpace.competency.problematicNucleus', fn ($nq) => $nq->where('program_id', $programId)))
            ->when($facultyId && ! $programId && ! $nucleusId && ! $competencyId && ! $spaceId && ! $topicId, fn ($q) => $q->whereHas('topic.academicSpace.competency.problematicNucleus.program', fn ($pq) => $pq->where('faculty_id', $facultyId)))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'faculties' => $faculties,
            'programs' => $programs,
            'nuclei' => $nuclei,
            'competencies' => $competencies,
            'academicSpaces' => $academicSpaces,
            'topics' => $topics,
            'activities' => $activities,
            'filters' => request()->only(['search', 'faculty_id', 'program_id', 'problematic_nucleus_id', 'competency_id', 'academic_space_id', 'topic_id', 'activity_id', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/create', [
            'activities' => Activity::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return to_route('admin.products.index')->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('admin/products/edit', [
            'product' => $product->load('activity'),
            'activities' => Activity::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return to_route('admin.products.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        $status = $product->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Producto {$status} exitosamente.");
    }
}
