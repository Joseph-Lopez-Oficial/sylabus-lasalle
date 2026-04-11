<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTopicRequest;
use App\Http\Requests\Admin\UpdateTopicRequest;
use App\Models\AcademicSpace;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function index(): Response
    {
        $topics = Topic::query()
            ->with('academicSpace')
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when(request('academic_space_id'), fn ($q, $id) => $q->where('academic_space_id', $id))
            ->orderBy('academic_space_id')
            ->orderBy('order')
            ->paginate(15)
            ->withQueryString();

        $academicSpaces = AcademicSpace::query()->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/topics/index', [
            'topics' => $topics,
            'academicSpaces' => $academicSpaces,
            'filters' => request()->only('search', 'academic_space_id'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/topics/create', [
            'academicSpaces' => AcademicSpace::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreTopicRequest $request): RedirectResponse
    {
        Topic::create($request->validated());

        return to_route('admin.topics.index')->with('success', 'Tema creado exitosamente.');
    }

    public function edit(Topic $topic): Response
    {
        return Inertia::render('admin/topics/edit', [
            'topic' => $topic->load('academicSpace'),
            'academicSpaces' => AcademicSpace::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateTopicRequest $request, Topic $topic): RedirectResponse
    {
        $topic->update($request->validated());

        return to_route('admin.topics.index')->with('success', 'Tema actualizado exitosamente.');
    }

    public function toggleStatus(Topic $topic): RedirectResponse
    {
        $topic->update(['is_active' => ! $topic->is_active]);

        $status = $topic->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Tema {$status} exitosamente.");
    }
}
