<?php

use App\Models\AcademicPeriod;
use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\Modality;
use App\Models\ProblematicNucleus;
use App\Models\Professor;
use App\Models\Program;
use App\Models\Programming;
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
    $this->professor = Professor::factory()->create();
    $this->modality = Modality::factory()->create();
    $this->period = AcademicPeriod::factory()->create(['name' => '2024-1']);
});

test('guest is redirected from programmings index', function () {
    $this->get(route('admin.programmings.index'))->assertRedirect(route('login'));
});

test('professor cannot access programmings index', function () {
    $prof = User::factory()->create(['role' => 'professor']);
    $this->actingAs($prof)->get(route('admin.programmings.index'))->assertForbidden();
});

test('admin can list programmings', function () {
    $otherPeriods = AcademicPeriod::factory()->count(2)->create();
    foreach ([$this->period, ...$otherPeriods] as $period) {
        Programming::factory()->create([
            'academic_space_id' => $this->academicSpace->id,
            'professor_id' => $this->professor->id,
            'modality_id' => $this->modality->id,
            'academic_period_id' => $period->id,
            'group' => null,
        ]);
    }

    $this->actingAs($this->admin)
        ->get(route('admin.programmings.index'))
        ->assertOk()
        ->assertInertia(
            fn($page) => $page->component('admin/programmings/index')
                ->has('programmings.data', 3)
        );
});

test('admin can create a programming', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.programmings.store'), [
            'academic_space_id' => $this->academicSpace->id,
            'professor_id' => $this->professor->id,
            'modality_id' => $this->modality->id,
            'academic_period_id' => $this->period->id,
            'group' => 'A',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.programmings.index'));

    expect(Programming::where('academic_period_id', $this->period->id)->exists())->toBeTrue();
});

test('store programming fails with missing period', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.programmings.store'), [
            'academic_space_id' => $this->academicSpace->id,
            'professor_id' => $this->professor->id,
            'modality_id' => $this->modality->id,
        ])
        ->assertSessionHasErrors('academic_period_id');
});

test('store programming fails with invalid professor', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.programmings.store'), [
            'academic_space_id' => $this->academicSpace->id,
            'professor_id' => 9999,
            'modality_id' => $this->modality->id,
            'academic_period_id' => $this->period->id,
        ])
        ->assertSessionHasErrors('professor_id');
});

test('admin can view programming detail', function () {
    $programming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->modality->id,
        'academic_period_id' => $this->period->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.programmings.show', $programming))
        ->assertOk()
        ->assertInertia(
            fn($page) => $page->component('admin/programmings/show')
                ->where('programming.id', $programming->id)
        );
});

test('admin can update a programming', function () {
    $programming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->modality->id,
        'academic_period_id' => $this->period->id,
    ]);

    $newPeriod = AcademicPeriod::factory()->create(['name' => '2024-2']);

    $this->actingAs($this->admin)
        ->put(route('admin.programmings.update', $programming), [
            'academic_space_id' => $this->academicSpace->id,
            'professor_id' => $this->professor->id,
            'modality_id' => $this->modality->id,
            'academic_period_id' => $newPeriod->id,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.programmings.index'));

    expect($programming->fresh()->academic_period_id)->toBe($newPeriod->id);
});

test('admin can toggle programming status', function () {
    $programming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->modality->id,
        'academic_period_id' => $this->period->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.programmings.toggle-status', $programming))
        ->assertRedirect();

    expect($programming->fresh()->is_active)->toBeFalse();
});
