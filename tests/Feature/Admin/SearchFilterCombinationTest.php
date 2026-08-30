<?php

use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('program search combined with a faculty filter does not leak other faculties', function () {
    $target = Faculty::factory()->create(['name' => 'Ingeniería', 'code' => 'ING']);
    $other = Faculty::factory()->create(['name' => 'Ciencias', 'code' => 'CIE']);

    Program::factory()->create([
        'faculty_id' => $target->id,
        'name' => 'Sistemas',
        'code' => 'SIS',
    ]);

    // Same searchable name, but belongs to a different faculty. An ungrouped
    // orWhere would pull this row in despite the faculty_id filter.
    Program::factory()->create([
        'faculty_id' => $other->id,
        'name' => 'Sistemas Biológicos',
        'code' => 'SBI',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.programs.index', [
            'search' => 'Sistemas',
            'faculty_id' => $target->id,
        ]))
        ->assertInertia(fn ($page) => $page
            ->has('programs.data', 1)
            ->where('programs.data.0.code', 'SIS')
        );
});

test('academic space search combined with a competency filter stays scoped', function () {
    $faculty = Faculty::factory()->create();
    $program = Program::factory()->create(['faculty_id' => $faculty->id]);
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $program->id]);

    $target = Competency::factory()->create([
        'problematic_nucleus_id' => $nucleus->id,
        'code' => 'C1',
    ]);
    $other = Competency::factory()->create([
        'problematic_nucleus_id' => $nucleus->id,
        'code' => 'C2',
    ]);

    AcademicSpace::factory()->create([
        'competency_id' => $target->id,
        'name' => 'Cálculo Diferencial',
        'code' => 'CAL1',
    ]);
    AcademicSpace::factory()->create([
        'competency_id' => $other->id,
        'name' => 'Cálculo Integral',
        'code' => 'CAL2',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.academic-spaces.index', [
            'search' => 'Cálculo',
            'competency_id' => $target->id,
        ]))
        ->assertInertia(fn ($page) => $page
            ->has('academicSpaces.data', 1)
            ->where('academicSpaces.data.0.code', 'CAL1')
        );
});

test('plain search without extra filters still matches every searchable column', function () {
    $faculty = Faculty::factory()->create(['name' => 'Ingeniería', 'code' => 'ZZZ']);
    Faculty::factory()->create(['name' => 'Ciencias', 'code' => 'CIE']);

    // Matches on code, not name.
    $this->actingAs($this->admin)
        ->get(route('admin.faculties.index', ['search' => 'ZZZ']))
        ->assertInertia(fn ($page) => $page
            ->has('faculties.data', 1)
            ->where('faculties.data.0.id', $faculty->id)
        );
});
