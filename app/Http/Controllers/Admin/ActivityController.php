<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreActivityRequest;
use App\Http\Requests\Admin\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(): Response
    {
        $activities = Activity::query()
            ->with(['topic.academicSpace', 'activityType'])
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when(request('topic_id'), fn ($q, $topicId) => $q->where('topic_id', $topicId))
            ->orderBy('topic_id')
            ->orderBy('order')
            ->paginate(15)
            ->withQueryString();

        $topics = Topic::query()->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/activities/index', [
            'activities' => $activities,
            'topics' => $topics,
            'filters' => request()->only('search', 'topic_id'),
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
