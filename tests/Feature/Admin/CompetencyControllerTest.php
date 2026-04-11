<?php

use App\Models\Competency;
use App\Models\Faculty;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->nucleus = ProblematicNucleus::factory()->create([
        'program_id' => Program::factory()->create([
            'faculty_id' => Faculty::factory()->create()->id,
        ])->id,
    ]);
});

test('guest is redirected from competencies index', function () {
    $this->get(route('admin.competencies.index'))->assertRedirect(route('login'));
});

test('professor cannot access competencies index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.competencies.index'))->assertForbidden();
});

test('admin can list competencies', function () {
    Competency::factory()->count(3)->create(['problematic_nucleus_id' => $this->nucleus->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.competencies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/competencies/index')
            ->has('competencies.data', 3)
        );
});

test('admin can create a competency', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.competencies.store'), [
            'problematic_nucleus_id' => $this->nucleus->id,
            'name' => 'Competencia de Prueba',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.competencies.index'));

    expect(Competency::where('name', 'Competencia de Prueba')->exists())->toBeTrue();
});

test('store competency fails with missing name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.competencies.store'), [
            'problematic_nucleus_id' => $this->nucleus->id,
        ])
        ->assertSessionHasErrors('name');
});

test('store competency fails with invalid nucleus', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.competencies.store'), [
            'problematic_nucleus_id' => 9999,
            'name' => 'Competencia',
        ])
        ->assertSessionHasErrors('problematic_nucleus_id');
});

test('admin can update a competency', function () {
    $competency = Competency::factory()->create(['problematic_nucleus_id' => $this->nucleus->id, 'name' => 'Original']);

    $this->actingAs($this->admin)
        ->put(route('admin.competencies.update', $competency), [
            'problematic_nucleus_id' => $this->nucleus->id,
            'name' => 'Actualizada',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.competencies.index'));

    expect($competency->fresh()->name)->toBe('Actualizada');
});

test('admin can toggle competency status', function () {
    $competency = Competency::factory()->create(['problematic_nucleus_id' => $this->nucleus->id, 'is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.competencies.toggle-status', $competency))
        ->assertRedirect();

    expect($competency->fresh()->is_active)->toBeFalse();
});
