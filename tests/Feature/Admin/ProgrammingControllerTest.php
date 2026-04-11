<?php

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
});

test('guest is redirected from programmings index', function () {
    $this->get(route('admin.programmings.index'))->assertRedirect(route('login'));
});

test('professor cannot access programmings index', function () {
    $prof = User::factory()->create(['role' => 'professor']);
    $this->actingAs($prof)->get(route('admin.programmings.index'))->assertForbidden();
});

test('admin can list programmings', function () {
    Programming::factory()->count(3)->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->modality->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.programmings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/programmings/index')
            ->has('programmings.data', 3)
        );
});

test('admin can create a programming', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.programmings.store'), [
            'academic_space_id' => $this->academicSpace->id,
            'professor_id' => $this->professor->id,
            'modality_id' => $this->modality->id,
            'period' => '2024-1',
            'group' => 'A',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.programmings.index'));

    expect(Programming::where('period', '2024-1')->exists())->toBeTrue();
});

test('store programming fails with missing period', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.programmings.store'), [
            'academic_space_id' => $this->academicSpace->id,
            'professor_id' => $this->professor->id,
            'modality_id' => $this->modality->id,
        ])
        ->assertSessionHasErrors('period');
});

test('store programming fails with invalid professor', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.programmings.store'), [
            'academic_space_id' => $this->academicSpace->id,
            'professor_id' => 9999,
            'modality_id' => $this->modality->id,
            'period' => '2024-1',
        ])
        ->assertSessionHasErrors('professor_id');
});

test('admin can view programming detail', function () {
    $programming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->modality->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.programmings.show', $programming))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/programmings/show')
            ->where('programming.id', $programming->id)
        );
});

test('admin can update a programming', function () {
    $programming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->modality->id,
        'period' => '2024-1',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.programmings.update', $programming), [
            'academic_space_id' => $this->academicSpace->id,
            'professor_id' => $this->professor->id,
            'modality_id' => $this->modality->id,
            'period' => '2024-2',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.programmings.index'));

    expect($programming->fresh()->period)->toBe('2024-2');
});

test('admin can toggle programming status', function () {
    $programming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->modality->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.programmings.toggle-status', $programming))
        ->assertRedirect();

    expect($programming->fresh()->is_active)->toBeFalse();
});
