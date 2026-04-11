<?php

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('guest is redirected from faculties index', function () {
    $this->get(route('admin.faculties.index'))->assertRedirect(route('login'));
});

test('professor cannot access faculties index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.faculties.index'))->assertForbidden();
});

test('admin can list faculties', function () {
    Faculty::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.faculties.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/faculties/index')
            ->has('faculties.data', 3)
        );
});

test('admin can filter faculties by search', function () {
    Faculty::factory()->create(['name' => 'Ingeniería']);
    Faculty::factory()->create(['name' => 'Medicina']);

    $this->actingAs($this->admin)
        ->get(route('admin.faculties.index', ['search' => 'Ingeniería']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('faculties.data', 1));
});

test('admin can see create faculty form', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.faculties.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/faculties/create'));
});

test('admin can create a faculty', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.faculties.store'), [
            'name' => 'Facultad de Ingeniería',
            'code' => 'ING',
            'description' => 'Facultad de prueba',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.faculties.index'));

    expect(Faculty::where('code', 'ING')->exists())->toBeTrue();
});

test('store faculty fails with missing name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.faculties.store'), [
            'code' => 'ING',
        ])
        ->assertSessionHasErrors('name');
});

test('store faculty fails with duplicate code', function () {
    Faculty::factory()->create(['code' => 'ING']);

    $this->actingAs($this->admin)
        ->post(route('admin.faculties.store'), [
            'name' => 'Otra Facultad',
            'code' => 'ING',
        ])
        ->assertSessionHasErrors('code');
});

test('admin can see edit faculty form', function () {
    $faculty = Faculty::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.faculties.edit', $faculty))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/faculties/edit')
            ->where('faculty.id', $faculty->id)
        );
});

test('admin can update a faculty', function () {
    $faculty = Faculty::factory()->create(['name' => 'Original']);

    $this->actingAs($this->admin)
        ->put(route('admin.faculties.update', $faculty), [
            'name' => 'Actualizada',
            'code' => $faculty->code,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.faculties.index'));

    expect($faculty->fresh()->name)->toBe('Actualizada');
});

test('admin can toggle faculty status', function () {
    $faculty = Faculty::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.faculties.toggle-status', $faculty))
        ->assertRedirect();

    expect($faculty->fresh()->is_active)->toBeFalse();
});
