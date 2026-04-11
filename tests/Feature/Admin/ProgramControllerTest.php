<?php

use App\Models\Faculty;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->faculty = Faculty::factory()->create();
});

test('guest is redirected from programs index', function () {
    $this->get(route('admin.programs.index'))->assertRedirect(route('login'));
});

test('professor cannot access programs index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.programs.index'))->assertForbidden();
});

test('admin can list programs', function () {
    Program::factory()->count(3)->create(['faculty_id' => $this->faculty->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.programs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/programs/index')
            ->has('programs.data', 3)
        );
});

test('admin can filter programs by faculty', function () {
    $otherFaculty = Faculty::factory()->create();
    Program::factory()->create(['faculty_id' => $this->faculty->id]);
    Program::factory()->create(['faculty_id' => $otherFaculty->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.programs.index', ['faculty_id' => $this->faculty->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('programs.data', 1));
});

test('admin can create a program', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.programs.store'), [
            'faculty_id' => $this->faculty->id,
            'name' => 'Ingeniería de Sistemas',
            'code' => 'ISC',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.programs.index'));

    expect(Program::where('code', 'ISC')->exists())->toBeTrue();
});

test('store program fails with invalid faculty', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.programs.store'), [
            'faculty_id' => 9999,
            'name' => 'Programa',
            'code' => 'PRG',
        ])
        ->assertSessionHasErrors('faculty_id');
});

test('store program fails with duplicate code', function () {
    Program::factory()->create(['code' => 'ISC', 'faculty_id' => $this->faculty->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.programs.store'), [
            'faculty_id' => $this->faculty->id,
            'name' => 'Otro Programa',
            'code' => 'ISC',
        ])
        ->assertSessionHasErrors('code');
});

test('admin can update a program', function () {
    $program = Program::factory()->create(['faculty_id' => $this->faculty->id, 'name' => 'Original']);

    $this->actingAs($this->admin)
        ->put(route('admin.programs.update', $program), [
            'faculty_id' => $this->faculty->id,
            'name' => 'Actualizado',
            'code' => $program->code,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.programs.index'));

    expect($program->fresh()->name)->toBe('Actualizado');
});

test('admin can toggle program status', function () {
    $program = Program::factory()->create(['faculty_id' => $this->faculty->id, 'is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.programs.toggle-status', $program))
        ->assertRedirect();

    expect($program->fresh()->is_active)->toBeFalse();
});
