<?php

use App\Models\AcademicPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('guest is redirected from academic periods index', function () {
    $this->get(route('admin.academic-periods.index'))->assertRedirect(route('login'));
});

test('professor cannot access academic periods index', function () {
    $prof = User::factory()->create(['role' => 'professor']);
    $this->actingAs($prof)->get(route('admin.academic-periods.index'))->assertForbidden();
});

test('admin can list academic periods', function () {
    AcademicPeriod::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.academic-periods.index'))
        ->assertOk()
        ->assertInertia(
            fn($page) => $page->component('admin/academic-periods/index')
                ->has('periods.data', 3)
        );
});

test('admin can create an academic period', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.academic-periods.store'), [
            'name' => '2024-1',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.academic-periods.index'));

    expect(AcademicPeriod::where('name', '2024-1')->exists())->toBeTrue();
});

test('store period fails with missing name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.academic-periods.store'), [])
        ->assertSessionHasErrors('name');
});

test('store period fails with duplicate name', function () {
    AcademicPeriod::factory()->create(['name' => '2024-1']);

    $this->actingAs($this->admin)
        ->post(route('admin.academic-periods.store'), ['name' => '2024-1'])
        ->assertSessionHasErrors('name');
});

test('admin can update an academic period', function () {
    $period = AcademicPeriod::factory()->create(['name' => '2024-1']);

    $this->actingAs($this->admin)
        ->put(route('admin.academic-periods.update', $period), [
            'name' => '2024-2',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.academic-periods.index'));

    expect($period->fresh()->name)->toBe('2024-2');
});

test('admin can toggle academic period status', function () {
    $period = AcademicPeriod::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.academic-periods.toggle-status', $period))
        ->assertRedirect();

    expect($period->fresh()->is_active)->toBeFalse();
});
