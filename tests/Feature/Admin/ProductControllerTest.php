<?php

use App\Models\AcademicSpace;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\ProblematicNucleus;
use App\Models\Product;
use App\Models\Program;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->activity = Activity::factory()->create([
        'topic_id' => Topic::factory()->create([
            'academic_space_id' => AcademicSpace::factory()->create([
                'competency_id' => Competency::factory()->create([
                    'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
                        'program_id' => Program::factory()->create([
                            'faculty_id' => Faculty::factory()->create()->id,
                        ])->id,
                    ])->id,
                ])->id,
            ])->id,
        ])->id,
        'activity_type_id' => ActivityType::factory()->create()->id,
    ]);
});

test('guest is redirected from products index', function () {
    $this->get(route('admin.products.index'))->assertRedirect(route('login'));
});

test('professor cannot access products index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.products.index'))->assertForbidden();
});

test('admin can list products', function () {
    Product::factory()->count(3)->create(['activity_id' => $this->activity->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/products/index')
            ->has('products.data', 3)
        );
});

test('admin can create a product', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'activity_id' => $this->activity->id,
            'name' => 'Informe escrito',
            'order' => 1,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.products.index'));

    expect(Product::where('name', 'Informe escrito')->exists())->toBeTrue();
});

test('store product fails with missing name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'activity_id' => $this->activity->id,
            'order' => 1,
        ])
        ->assertSessionHasErrors('name');
});

test('store product fails with invalid activity', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'activity_id' => 9999,
            'name' => 'Producto',
            'order' => 1,
        ])
        ->assertSessionHasErrors('activity_id');
});

test('admin can update a product', function () {
    $product = Product::factory()->create(['activity_id' => $this->activity->id, 'name' => 'Original']);

    $this->actingAs($this->admin)
        ->put(route('admin.products.update', $product), [
            'activity_id' => $this->activity->id,
            'name' => 'Actualizado',
            'order' => $product->order,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.products.index'));

    expect($product->fresh()->name)->toBe('Actualizado');
});

test('admin can toggle product status', function () {
    $product = Product::factory()->create(['activity_id' => $this->activity->id, 'is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.products.toggle-status', $product))
        ->assertRedirect();

    expect($product->fresh()->is_active)->toBeFalse();
});
