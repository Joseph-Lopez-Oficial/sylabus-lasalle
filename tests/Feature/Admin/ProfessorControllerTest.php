<?php

use App\Models\Professor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('guest is redirected from professors index', function () {
    $this->get(route('admin.professors.index'))->assertRedirect(route('login'));
});

test('professor cannot access professors index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.professors.index'))->assertForbidden();
});

test('admin can list professors', function () {
    Professor::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.professors.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/professors/index')
            ->has('professors.data', 3)
        );
});

test('admin can create a professor without user account', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.professors.store'), [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'document_number' => '12345678',
            'institutional_email' => 'juan.perez@lasalle.edu.co',
            'is_active' => true,
            'create_user' => false,
        ])
        ->assertRedirect(route('admin.professors.index'));

    expect(Professor::where('document_number', '12345678')->exists())->toBeTrue();
    expect(User::where('email', 'juan.perez@lasalle.edu.co')->exists())->toBeFalse();
});

test('admin can create a professor with user account', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.professors.store'), [
            'first_name' => 'María',
            'last_name' => 'López',
            'document_number' => '87654321',
            'institutional_email' => 'maria.lopez@lasalle.edu.co',
            'is_active' => true,
            'create_user' => true,
            'user_email' => 'maria.lopez@sistema.co',
            'user_password' => 'secret123',
            'user_password_confirmation' => 'secret123',
        ])
        ->assertRedirect(route('admin.professors.index'));

    $professor = Professor::where('document_number', '87654321')->first();
    expect($professor)->not->toBeNull();
    expect($professor->user_id)->not->toBeNull();
    expect(User::find($professor->user_id)?->role)->toBe('professor');
});

test('store professor fails when create_user is true but user fields are missing', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.professors.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'document_number' => '99999999',
            'institutional_email' => 'test@lasalle.edu.co',
            'create_user' => true,
        ])
        ->assertSessionHasErrors(['user_email', 'user_password']);
});

test('store professor fails with duplicate document number', function () {
    Professor::factory()->create(['document_number' => '12345678']);

    $this->actingAs($this->admin)
        ->post(route('admin.professors.store'), [
            'first_name' => 'Otro',
            'last_name' => 'Profesor',
            'document_number' => '12345678',
            'institutional_email' => 'otro@lasalle.edu.co',
        ])
        ->assertSessionHasErrors('document_number');
});

test('admin can update a professor', function () {
    $professor = Professor::factory()->create(['first_name' => 'Original']);

    $this->actingAs($this->admin)
        ->put(route('admin.professors.update', $professor), [
            'first_name' => 'Actualizado',
            'last_name' => $professor->last_name,
            'document_number' => $professor->document_number,
            'institutional_email' => $professor->institutional_email,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.professors.index'));

    expect($professor->fresh()->first_name)->toBe('Actualizado');
});

test('admin can toggle professor status', function () {
    $professor = Professor::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.professors.toggle-status', $professor))
        ->assertRedirect();

    expect($professor->fresh()->is_active)->toBeFalse();
});

test('admin can import students via excel', function () {
    Excel::fake();

    $this->actingAs($this->admin)
        ->post(route('admin.professors.import-students'), [
            'file' => UploadedFile::fake()->create('students.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ])
        ->assertRedirect();

    Excel::assertImported('students.xlsx');
});

test('import students fails without file', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.professors.import-students'), [])
        ->assertSessionHasErrors('file');
});
