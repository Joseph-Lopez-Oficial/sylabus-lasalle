<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PaginatesListings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicPeriodRequest;
use App\Http\Requests\Admin\UpdateAcademicPeriodRequest;
use App\Models\AcademicPeriod;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPeriodController extends Controller
{
    use PaginatesListings;

    public function index(): Response
    {
        $periods = AcademicPeriod::query()
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('name')
            ->paginate($this->perPage())
            ->withQueryString();

        return Inertia::render('admin/academic-periods/index', [
            'periods' => $periods,
            'filters' => request()->only('search', 'per_page'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/academic-periods/create');
    }

    public function store(StoreAcademicPeriodRequest $request): RedirectResponse
    {
        AcademicPeriod::create($request->validated());

        return to_route('admin.academic-periods.index')->with('success', 'Período académico creado exitosamente.');
    }

    public function edit(AcademicPeriod $academicPeriod): Response
    {
        return Inertia::render('admin/academic-periods/edit', [
            'period' => $academicPeriod,
        ]);
    }

    public function update(UpdateAcademicPeriodRequest $request, AcademicPeriod $academicPeriod): RedirectResponse
    {
        $academicPeriod->update($request->validated());

        return to_route('admin.academic-periods.index')->with('success', 'Período académico actualizado exitosamente.');
    }

    public function toggleStatus(AcademicPeriod $academicPeriod): RedirectResponse
    {
        $academicPeriod->update(['is_active' => ! $academicPeriod->is_active]);

        $status = $academicPeriod->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Período académico {$status} exitosamente.");
    }
}
