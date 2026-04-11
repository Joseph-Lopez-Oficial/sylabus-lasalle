<?php

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('guest is redirected from students index', function () {
    $this->get(route('admin.students.index'))->assertRedirect(route('login'));
});

test('professor cannot access students index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.students.index'))->assertForbidden();
});

test('admin can list students', function () {
    Student::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.students.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/students/index')
            ->has('students.data', 3)
        );
});

test('admin can filter students by search', function () {
    Student::factory()->create(['first_name' => 'Carlos', 'document_number' => '111']);
    Student::factory()->create(['first_name' => 'Luisa', 'document_number' => '222']);

    $this->actingAs($this->admin)
        ->get(route('admin.students.index', ['search' => 'Carlos']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('students.data', 1));
});

test('admin can create a student', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.students.store'), [
            'first_name' => 'Ana',
            'last_name' => 'García',
            'document_number' => '20230001',
            'email' => 'ana.garcia@estudiante.lasalle.edu.co',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.students.index'));

    expect(Student::where('document_number', '20230001')->exists())->toBeTrue();
});

test('store student fails with duplicate document number', function () {
    Student::factory()->create(['document_number' => '20230001']);

    $this->actingAs($this->admin)
        ->post(route('admin.students.store'), [
            'first_name' => 'Otro',
            'last_name' => 'Estudiante',
            'document_number' => '20230001',
            'email' => 'otro@lasalle.edu.co',
        ])
        ->assertSessionHasErrors('document_number');
});

test('store student fails with missing required fields', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.students.store'), [
            'document_number' => '20230002',
        ])
        ->assertSessionHasErrors(['first_name', 'last_name', 'email']);
});

test('admin can update a student', function () {
    $student = Student::factory()->create(['first_name' => 'Original']);

    $this->actingAs($this->admin)
        ->put(route('admin.students.update', $student), [
            'first_name' => 'Actualizado',
            'last_name' => $student->last_name,
            'document_number' => $student->document_number,
            'email' => $student->email,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.students.index'));

    expect($student->fresh()->first_name)->toBe('Actualizado');
});

test('admin can toggle student status', function () {
    $student = Student::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.students.toggle-status', $student))
        ->assertRedirect();

    expect($student->fresh()->is_active)->toBeFalse();
});
