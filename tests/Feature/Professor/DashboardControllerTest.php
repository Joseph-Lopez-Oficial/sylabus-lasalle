<?php

use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Enrollment;
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
    $this->professorUser = User::factory()->create(['role' => 'professor']);
    $this->professor = Professor::factory()->create(['user_id' => $this->professorUser->id, 'is_active' => true]);

    $this->academicSpace = AcademicSpace::factory()->create([
        'competency_id' => Competency::factory()->create([
            'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
                'program_id' => Program::factory()->create([
                    'faculty_id' => Faculty::factory()->create()->id,
                ])->id,
            ])->id,
        ])->id,
    ]);
    $this->modality = Modality::factory()->create();
});

test('guest is redirected from professor dashboard', function () {
    $this->get(route('professor.dashboard'))->assertRedirect(route('login'));
});

test('unauthenticated user cannot access professor dashboard', function () {
    $this->get(route('professor.dashboard'))->assertRedirect(route('login'));
});

test('professor can access their dashboard', function () {
    $this->actingAs($this->professorUser)
        ->get(route('professor.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('professor/dashboard')
            ->has('programmings')
        );
});

test('dashboard shows only active programmings assigned to the professor', function () {
    // Programación propia activa
    Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->modality->id,
        'is_active' => true,
    ]);

    // Programación propia inactiva — no debe aparecer
    Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->modality->id,
        'is_active' => false,
    ]);

    // Programación de otro profesor — no debe aparecer
    Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => $this->modality->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->professorUser)
        ->get(route('professor.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('programmings', 1));
});

test('dashboard includes enrolled count for each programming', function () {
    $programming = Programming::factory()->create([
        'academic_space_id' => $this->academicSpace->id,
        'professor_id' => $this->professor->id,
        'modality_id' => $this->modality->id,
        'is_active' => true,
    ]);

    Enrollment::factory()->count(3)->create([
        'programming_id' => $programming->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->professorUser)
        ->get(route('professor.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('programmings.0.enrolled_count', 3));
});
