<?php

use App\Models\Competency;
use App\Models\Faculty;
use App\Models\MesocurricularLearningOutcome;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->competency = Competency::factory()->create([
        'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
            'program_id' => Program::factory()->create([
                'faculty_id' => Faculty::factory()->create()->id,
            ])->id,
        ])->id,
    ]);
});

test('guest is redirected from mesocurricular outcomes index', function () {
    $this->get(route('admin.mesocurricular-outcomes.index'))->assertRedirect(route('login'));
});

test('professor cannot access mesocurricular outcomes index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.mesocurricular-outcomes.index'))->assertForbidden();
});

test('admin can list mesocurricular outcomes', function () {
    MesocurricularLearningOutcome::factory()->count(3)->create(['competency_id' => $this->competency->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.mesocurricular-outcomes.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page->component('admin/mesocurricular-outcomes/index')
                ->has('outcomes.data', 3)
        );
});

test('admin can create a mesocurricular outcome', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.mesocurricular-outcomes.store'), [
            'code' => 'RM1',
            'competency_id' => $this->competency->id,
            'description' => 'El estudiante comprende los fundamentos del área.',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.mesocurricular-outcomes.index'));

    expect(MesocurricularLearningOutcome::where('code', 'RM1')->exists())->toBeTrue();
});

test('store outcome fails with missing code', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.mesocurricular-outcomes.store'), [
            'competency_id' => $this->competency->id,
            'description' => 'Descripción.',
        ])
        ->assertSessionHasErrors('code');
});

test('store outcome fails with invalid code format', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.mesocurricular-outcomes.store'), [
            'code' => 'X1',
            'competency_id' => $this->competency->id,
            'description' => 'Descripción.',
        ])
        ->assertSessionHasErrors('code');
});

test('store outcome fails with duplicate code in same competency', function () {
    MesocurricularLearningOutcome::factory()->create(['code' => 'RM1', 'competency_id' => $this->competency->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.mesocurricular-outcomes.store'), [
            'code' => 'RM1',
            'competency_id' => $this->competency->id,
            'description' => 'Otra descripción.',
        ])
        ->assertSessionHasErrors('code');
});

test('same code can exist in different competencies', function () {
    $otherCompetency = Competency::factory()->create([
        'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
            'program_id' => Program::factory()->create([
                'faculty_id' => Faculty::factory()->create()->id,
            ])->id,
        ])->id,
    ]);

    MesocurricularLearningOutcome::factory()->create(['code' => 'RM1', 'competency_id' => $this->competency->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.mesocurricular-outcomes.store'), [
            'code' => 'RM1',
            'competency_id' => $otherCompetency->id,
            'description' => 'Resultado en otra competencia.',
        ])
        ->assertRedirect(route('admin.mesocurricular-outcomes.index'));
});

test('store outcome fails with missing description', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.mesocurricular-outcomes.store'), [
            'code' => 'RM1',
            'competency_id' => $this->competency->id,
        ])
        ->assertSessionHasErrors('description');
});

test('store outcome fails with invalid competency', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.mesocurricular-outcomes.store'), [
            'code' => 'RM1',
            'competency_id' => 9999,
            'description' => 'Descripción',
        ])
        ->assertSessionHasErrors('competency_id');
});

test('admin can update a mesocurricular outcome', function () {
    $outcome = MesocurricularLearningOutcome::factory()->create(['code' => 'RM1', 'competency_id' => $this->competency->id]);

    $this->actingAs($this->admin)
        ->put(route('admin.mesocurricular-outcomes.update', $outcome), [
            'code' => 'RM1',
            'competency_id' => $this->competency->id,
            'description' => 'Descripción actualizada.',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.mesocurricular-outcomes.index'));

    expect($outcome->fresh()->description)->toBe('Descripción actualizada.');
});

test('admin can toggle mesocurricular outcome status', function () {
    $outcome = MesocurricularLearningOutcome::factory()->create(['competency_id' => $this->competency->id, 'is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.mesocurricular-outcomes.toggle-status', $outcome))
        ->assertRedirect();

    expect($outcome->fresh()->is_active)->toBeFalse();
});
