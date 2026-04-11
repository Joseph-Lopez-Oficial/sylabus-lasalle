<?php

use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use App\Models\Topic;
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
});

test('guest is redirected from topics index', function () {
    $this->get(route('admin.topics.index'))->assertRedirect(route('login'));
});

test('professor cannot access topics index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.topics.index'))->assertForbidden();
});

test('admin can list topics', function () {
    Topic::factory()->count(3)->create(['academic_space_id' => $this->academicSpace->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.topics.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/topics/index')
            ->has('topics.data', 3)
        );
});

test('admin can create a topic', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.topics.store'), [
            'academic_space_id' => $this->academicSpace->id,
            'name' => 'Introducción a la programación',
            'order' => 1,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.topics.index'));

    expect(Topic::where('name', 'Introducción a la programación')->exists())->toBeTrue();
});

test('store topic fails with missing name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.topics.store'), [
            'academic_space_id' => $this->academicSpace->id,
            'order' => 1,
        ])
        ->assertSessionHasErrors('name');
});

test('store topic fails with invalid academic space', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.topics.store'), [
            'academic_space_id' => 9999,
            'name' => 'Tema',
            'order' => 1,
        ])
        ->assertSessionHasErrors('academic_space_id');
});

test('admin can update a topic', function () {
    $topic = Topic::factory()->create(['academic_space_id' => $this->academicSpace->id, 'name' => 'Original']);

    $this->actingAs($this->admin)
        ->put(route('admin.topics.update', $topic), [
            'academic_space_id' => $this->academicSpace->id,
            'name' => 'Actualizado',
            'order' => $topic->order,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.topics.index'));

    expect($topic->fresh()->name)->toBe('Actualizado');
});

test('admin can toggle topic status', function () {
    $topic = Topic::factory()->create(['academic_space_id' => $this->academicSpace->id, 'is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.topics.toggle-status', $topic))
        ->assertRedirect();

    expect($topic->fresh()->is_active)->toBeFalse();
});
