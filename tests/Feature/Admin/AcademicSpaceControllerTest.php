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
    $this->competency = Competency::factory()->create([
        'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
            'program_id' => Program::factory()->create([
                'faculty_id' => Faculty::factory()->create()->id,
            ])->id,
        ])->id,
    ]);
});

test('guest is redirected from academic spaces index', function () {
    $this->get(route('admin.academic-spaces.index'))->assertRedirect(route('login'));
});

test('professor cannot access academic spaces index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.academic-spaces.index'))->assertForbidden();
});

test('admin can list academic spaces', function () {
    AcademicSpace::factory()->count(3)->create(['competency_id' => $this->competency->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.academic-spaces.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/academic-spaces/index')
            ->has('academicSpaces.data', 3)
        );
});

test('admin can create an academic space', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.academic-spaces.store'), [
            'competency_id' => $this->competency->id,
            'name' => 'Programación I',
            'code' => 'PRG1',
            'credits' => 3,
            'semester' => 2,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.academic-spaces.index'));

    expect(AcademicSpace::where('code', 'PRG1')->exists())->toBeTrue();
});

test('store academic space fails with missing name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.academic-spaces.store'), [
            'competency_id' => $this->competency->id,
            'code' => 'PRG1',
            'credits' => 3,
        ])
        ->assertSessionHasErrors('name');
});

test('store academic space fails with duplicate code', function () {
    AcademicSpace::factory()->create(['code' => 'PRG1', 'competency_id' => $this->competency->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.academic-spaces.store'), [
            'competency_id' => $this->competency->id,
            'name' => 'Otro Espacio',
            'code' => 'PRG1',
            'credits' => 3,
        ])
        ->assertSessionHasErrors('code');
});

test('admin can view academic space detail', function () {
    $space = AcademicSpace::factory()->create(['competency_id' => $this->competency->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.academic-spaces.show', $space))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/academic-spaces/show')
            ->where('academicSpace.id', $space->id)
        );
});

test('admin can update an academic space', function () {
    $space = AcademicSpace::factory()->create(['competency_id' => $this->competency->id, 'name' => 'Original']);

    $this->actingAs($this->admin)
        ->put(route('admin.academic-spaces.update', $space), [
            'competency_id' => $this->competency->id,
            'name' => 'Actualizado',
            'code' => $space->code,
            'credits' => $space->credits,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.academic-spaces.index'));

    expect($space->fresh()->name)->toBe('Actualizado');
});

test('admin can toggle academic space status', function () {
    $space = AcademicSpace::factory()->create(['competency_id' => $this->competency->id, 'is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.academic-spaces.toggle-status', $space))
        ->assertRedirect();

    expect($space->fresh()->is_active)->toBeFalse();
});
