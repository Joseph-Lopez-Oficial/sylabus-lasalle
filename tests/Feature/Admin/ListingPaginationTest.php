<?php

use App\Models\Faculty;
use App\Models\Student;
use App\Models\User;
use App\Support\ListingPageSize;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('a listing serves the default page size when none is asked for', function () {
    Student::factory()->count(20)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.students.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('students.per_page', 15)
            ->where('students.total', 20)
            ->count('students.data', 15)
        );
});

test('a listing honours an allowed page size', function () {
    Student::factory()->count(60)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.students.index', ['per_page' => 50]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('students.per_page', 50)
            ->count('students.data', 50)
            ->where('filters.per_page', '50')
        );
});

test('a size outside the allowed list falls back to the default', function (mixed $size) {
    Student::factory()->count(20)->create();

    // Serving every row because a crafted request asked for it would put
    // hundreds of records on one page.
    $this->actingAs($this->admin)
        ->get(route('admin.students.index', ['per_page' => $size]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('students.per_page', 15));
})->with([
    'un número enorme' => 100000,
    'cero' => 0,
    'negativo' => -5,
    'texto' => 'todos',
    'uno no permitido' => 30,
]);

test('the page travels in the address', function () {
    Student::factory()->count(40)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.students.index', ['page' => 3]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('students.current_page', 3)
            ->where('students.from', 31)
        );
});

test('search and page size survive together', function () {
    Student::factory()->count(30)->create(['last_name' => 'Ordinario']);
    Student::factory()->count(30)->create(['last_name' => 'Buscado']);

    $this->actingAs($this->admin)
        ->get(route('admin.students.index', ['search' => 'Buscado', 'per_page' => 25]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('students.total', 30)
            ->where('students.per_page', 25)
            ->where('filters.search', 'Buscado')
            ->where('filters.per_page', '25')
        );
});

test('the page size reaches every listing that paginates', function (string $route, string $prop) {
    $this->actingAs($this->admin)
        ->get(route($route, ['per_page' => 25]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where("{$prop}.per_page", 25));
})->with([
    'facultades' => ['admin.faculties.index', 'faculties'],
    'programas' => ['admin.programs.index', 'programs'],
    'núcleos problémicos' => ['admin.problematic-nuclei.index', 'nuclei'],
    'competencias' => ['admin.competencies.index', 'competencies'],
    'espacios académicos' => ['admin.academic-spaces.index', 'academicSpaces'],
    'temas' => ['admin.topics.index', 'topics'],
    'actividades' => ['admin.activities.index', 'activities'],
    'productos' => ['admin.products.index', 'products'],
    'profesores' => ['admin.professors.index', 'professors'],
    'estudiantes' => ['admin.students.index', 'students'],
    'programaciones' => ['admin.programmings.index', 'programmings'],
    'períodos académicos' => ['admin.academic-periods.index', 'periods'],
]);

test('the allowed sizes are a closed list', function () {
    // The interface offers exactly these, and the server accepts no others.
    expect(ListingPageSize::ALLOWED)->toBe([15, 25, 50, 100]);
});

test('a listing keeps its filters when the page changes', function () {
    $faculty = Faculty::factory()->create();
    \App\Models\Program::factory()->count(20)->create(['faculty_id' => $faculty->id]);
    \App\Models\Program::factory()->count(20)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.programs.index', ['faculty_id' => $faculty->id, 'page' => 2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('programs.total', 20)
            ->where('programs.current_page', 2)
            ->where('filters.faculty_id', (string) $faculty->id)
        );
});
