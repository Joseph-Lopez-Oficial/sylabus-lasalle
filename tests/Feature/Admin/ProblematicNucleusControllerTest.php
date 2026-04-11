<?php

use App\Models\Faculty;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->program = Program::factory()->create([
        'faculty_id' => Faculty::factory()->create()->id,
    ]);
});

test('guest is redirected from problematic nuclei index', function () {
    $this->get(route('admin.problematic-nuclei.index'))->assertRedirect(route('login'));
});

test('professor cannot access problematic nuclei index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.problematic-nuclei.index'))->assertForbidden();
});

test('admin can list problematic nuclei', function () {
    ProblematicNucleus::factory()->count(3)->create(['program_id' => $this->program->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.problematic-nuclei.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/problematic-nuclei/index')
            ->has('nuclei.data', 3)
        );
});

test('admin can create a problematic nucleus', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.problematic-nuclei.store'), [
            'program_id' => $this->program->id,
            'name' => 'Núcleo de Prueba',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.problematic-nuclei.index'));

    expect(ProblematicNucleus::where('name', 'Núcleo de Prueba')->exists())->toBeTrue();
});

test('store nucleus fails with missing name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.problematic-nuclei.store'), [
            'program_id' => $this->program->id,
        ])
        ->assertSessionHasErrors('name');
});

test('store nucleus fails with invalid program', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.problematic-nuclei.store'), [
            'program_id' => 9999,
            'name' => 'Núcleo',
        ])
        ->assertSessionHasErrors('program_id');
});

test('admin can update a problematic nucleus', function () {
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $this->program->id, 'name' => 'Original']);

    $this->actingAs($this->admin)
        ->put(route('admin.problematic-nuclei.update', $nucleus), [
            'program_id' => $this->program->id,
            'name' => 'Actualizado',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.problematic-nuclei.index'));

    expect($nucleus->fresh()->name)->toBe('Actualizado');
});

test('admin can toggle nucleus status', function () {
    $nucleus = ProblematicNucleus::factory()->create(['program_id' => $this->program->id, 'is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.problematic-nuclei.toggle-status', $nucleus))
        ->assertRedirect();

    expect($nucleus->fresh()->is_active)->toBeFalse();
});
