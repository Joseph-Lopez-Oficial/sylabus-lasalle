<?php

use App\Models\AcademicSpace;
use App\Models\Activity;
use App\Models\ActivityType;
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
    $this->topic = Topic::factory()->create([
        'academic_space_id' => AcademicSpace::factory()->create([
            'competency_id' => Competency::factory()->create([
                'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
                    'program_id' => Program::factory()->create([
                        'faculty_id' => Faculty::factory()->create()->id,
                    ])->id,
                ])->id,
            ])->id,
        ])->id,
    ]);
    $this->activityType = ActivityType::factory()->create();
});

test('guest is redirected from activities index', function () {
    $this->get(route('admin.activities.index'))->assertRedirect(route('login'));
});

test('professor cannot access activities index', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $this->actingAs($professor)->get(route('admin.activities.index'))->assertForbidden();
});

test('admin can list activities', function () {
    Activity::factory()->count(3)->create([
        'topic_id' => $this->topic->id,
        'activity_type_id' => $this->activityType->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.activities.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/activities/index')
            ->has('activities.data', 3)
        );
});

test('admin can create an activity', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.activities.store'), [
            'topic_id' => $this->topic->id,
            'activity_type_id' => $this->activityType->id,
            'name' => 'Taller práctico',
            'order' => 1,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.activities.index'));

    expect(Activity::where('name', 'Taller práctico')->exists())->toBeTrue();
});

test('store activity fails with missing name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.activities.store'), [
            'topic_id' => $this->topic->id,
            'activity_type_id' => $this->activityType->id,
            'order' => 1,
        ])
        ->assertSessionHasErrors('name');
});

test('store activity fails with invalid activity type', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.activities.store'), [
            'topic_id' => $this->topic->id,
            'activity_type_id' => 9999,
            'name' => 'Actividad',
            'order' => 1,
        ])
        ->assertSessionHasErrors('activity_type_id');
});

test('admin can update an activity', function () {
    $activity = Activity::factory()->create([
        'topic_id' => $this->topic->id,
        'activity_type_id' => $this->activityType->id,
        'name' => 'Original',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.activities.update', $activity), [
            'topic_id' => $this->topic->id,
            'activity_type_id' => $this->activityType->id,
            'name' => 'Actualizada',
            'order' => $activity->order,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.activities.index'));

    expect($activity->fresh()->name)->toBe('Actualizada');
});

test('admin can toggle activity status', function () {
    $activity = Activity::factory()->create([
        'topic_id' => $this->topic->id,
        'activity_type_id' => $this->activityType->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.activities.toggle-status', $activity))
        ->assertRedirect();

    expect($activity->fresh()->is_active)->toBeFalse();
});
