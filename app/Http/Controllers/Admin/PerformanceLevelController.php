<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePerformanceLevelRequest;
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
            ->get(['id', 'name', 'description', 'order', 'grade_value', 'is_below_basic_threshold', 'is_active']);

        return Inertia::render('admin/performance-levels/index', [
            'levels' => $levels,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/performance-levels/create');
    }

    public function store(StorePerformanceLevelRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Only one level can define the threshold, so marking the new one
        // clears the flag everywhere else.
        if ($validated['is_below_basic_threshold'] ?? false) {
            PerformanceLevel::query()->update(['is_below_basic_threshold' => false]);
        }

        PerformanceLevel::create($validated);

        return to_route('admin.performance-levels.index')
            ->with('success', 'Nivel de desempeño creado exitosamente.');
    }

    public function edit(PerformanceLevel $performanceLevel): Response
    {
        return Inertia::render('admin/performance-levels/edit', [
            'level' => $performanceLevel->only([
                'id', 'name', 'description', 'order', 'grade_value', 'is_below_basic_threshold', 'is_active',
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

    public function toggleStatus(PerformanceLevel $performanceLevel): RedirectResponse
    {
        // Retiring a level still in use would hide the marks recorded with it,
        // and retiring the threshold level would leave the at-risk report
        // without a reference, so both cases are refused.
        if ($performanceLevel->is_active) {
            $gradesCount = Grade::where('performance_level_id', $performanceLevel->id)->count();

            if ($gradesCount > 0) {
                return back()->with(
                    'error',
                    "No se puede desactivar este nivel porque {$gradesCount} calificación(es) lo usan."
                );
            }

            if ($performanceLevel->is_below_basic_threshold) {
                return back()->with(
                    'error',
                    'No se puede desactivar el nivel marcado como umbral de bajo rendimiento. Marque otro nivel como umbral antes de desactivarlo.'
                );
            }
        }

        $performanceLevel->update(['is_active' => ! $performanceLevel->is_active]);

        $status = $performanceLevel->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Nivel {$status} exitosamente.");
    }
}
