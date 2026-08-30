<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePerformanceLevelRequest;
use App\Models\Grade;
use App\Models\PerformanceLevel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceLevelController extends Controller
{
    public function index(): Response
    {
        $levels = PerformanceLevel::query()
            ->withCount('grades')
            ->orderBy('order')
            ->get(['id', 'name', 'description', 'order', 'grade_value', 'is_below_basic_threshold']);

        return Inertia::render('admin/performance-levels/index', [
            'levels' => $levels,
        ]);
    }

    public function edit(PerformanceLevel $performanceLevel): Response
    {
        return Inertia::render('admin/performance-levels/edit', [
            'level' => $performanceLevel->only([
                'id', 'name', 'description', 'order', 'grade_value', 'is_below_basic_threshold',
            ]),
            'gradesCount' => Grade::where('performance_level_id', $performanceLevel->id)->count(),
        ]);
    }

    public function update(UpdatePerformanceLevelRequest $request, PerformanceLevel $performanceLevel): RedirectResponse
    {
        $validated = $request->validated();

        // Only one level can define the threshold, so marking this one clears
        // the flag everywhere else.
        if ($validated['is_below_basic_threshold'] ?? false) {
            PerformanceLevel::query()
                ->whereKeyNot($performanceLevel->id)
                ->update(['is_below_basic_threshold' => false]);
        }

        $performanceLevel->update($validated);

        return to_route('admin.performance-levels.index')
            ->with('success', 'Nivel de desempeño actualizado exitosamente.');
    }
}
