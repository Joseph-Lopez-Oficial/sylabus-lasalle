<?php

use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcomeType;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->academicSpace = AcademicSpace::factory()->create([
        'competency_id' => Competency::factory()->create([
            'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
                'program_id' => Program::factory()->create([
                    'faculty_id' => Faculty::factory()->create()->id,
                ])->id,
            ])->id,
        ])->id,
    ]);
    $this->type = MicrocurricularLearningOutcomeType::factory()->create();
});

test('guest is redirected from microcurricular outcomes index', function () {
    $this->get(route('admin.microcurricular-outcomes.index'))->assertRedirect(route('login'));
});

test('professor cannot access microcurricular outcomes index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.microcurricular-outcomes.index'))->assertForbidden();
});

test('admin can list microcurricular outcomes', function () {
    MicrocurricularLearningOutcome::factory()->count(3)->create([
        'academic_space_id' => $this->academicSpace->id,
        'type_id' => $this->type->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.microcurricular-outcomes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/microcurricular-outcomes/index')
            ->has('outcomes.data', 3)
        );
});

test('admin can create a microcurricular outcome', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.microcurricular-outcomes.store'), [
            'academic_space_id' => $this->academicSpace->id,
            'type_id' => $this->type->id,
            'description' => 'El estudiante aplica conceptos fundamentales.',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.microcurricular-outcomes.index'));

    expect(MicrocurricularLearningOutcome::where('academic_space_id', $this->academicSpace->id)->exists())->toBeTrue();
});

test('store microcurricular outcome fails with missing description', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.microcurricular-outcomes.store'), [
            'academic_space_id' => $this->academicSpace->id,
            'type_id' => $this->type->id,
        ])
        ->assertSessionHasErrors('description');
});

test('store microcurricular outcome fails with invalid type', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.microcurricular-outcomes.store'), [
            'academic_space_id' => $this->academicSpace->id,
            'type_id' => 9999,
            'description' => 'Descripción',
        ])
        ->assertSessionHasErrors('type_id');
});

test('admin can update a microcurricular outcome', function () {
    $outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'type_id' => $this->type->id,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.microcurricular-outcomes.update', $outcome), [
            'academic_space_id' => $this->academicSpace->id,
            'type_id' => $this->type->id,
            'description' => 'Descripción actualizada.',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.microcurricular-outcomes.index'));

    expect($outcome->fresh()->description)->toBe('Descripción actualizada.');
});

test('admin can toggle microcurricular outcome status', function () {
    $outcome = MicrocurricularLearningOutcome::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'type_id' => $this->type->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.microcurricular-outcomes.toggle-status', $outcome))
        ->assertRedirect();

    expect($outcome->fresh()->is_active)->toBeFalse();
});
