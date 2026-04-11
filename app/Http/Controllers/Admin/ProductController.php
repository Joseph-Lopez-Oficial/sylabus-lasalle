<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Activity;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->with('activity.topic')
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when(request('activity_id'), fn ($q, $activityId) => $q->where('activity_id', $activityId))
            ->orderBy('activity_id')
            ->orderBy('order')
            ->paginate(15)
            ->withQueryString();

        $activities = Activity::query()->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'activities' => $activities,
            'filters' => request()->only('search', 'activity_id'),
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
