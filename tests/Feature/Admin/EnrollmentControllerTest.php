<?php

use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Modality;
use App\Models\ProblematicNucleus;
use App\Models\Professor;
use App\Models\Program;
use App\Models\Programming;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->programming = Programming::factory()->create([
        'academic_space_id' => AcademicSpace::factory()->create([
            'competency_id' => Competency::factory()->create([
                'problematic_nucleus_id' => ProblematicNucleus::factory()->create([
                    'program_id' => Program::factory()->create([
                        'faculty_id' => Faculty::factory()->create()->id,
                    ])->id,
                ])->id,
            ])->id,
        ])->id,
        'professor_id' => Professor::factory()->create()->id,
        'modality_id' => Modality::factory()->create()->id,
    ]);
    $this->student = Student::factory()->create(['is_active' => true]);
});

test('admin can enroll a student individually', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.programmings.enrollments.store', $this->programming), [
            'student_id' => $this->student->id,
        ])
        ->assertRedirect();

    expect(Enrollment::where('programming_id', $this->programming->id)
        ->where('student_id', $this->student->id)
        ->exists()
    )->toBeTrue();
});

test('cannot enroll same student twice', function () {
    Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => $this->student->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.programmings.enrollments.store', $this->programming), [
            'student_id' => $this->student->id,
        ])
        ->assertSessionHasErrors('student_id');
});

test('enroll fails with non-existent student', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.programmings.enrollments.store', $this->programming), [
            'student_id' => 9999,
        ])
        ->assertSessionHasErrors('student_id');
});

test('admin can toggle enrollment status', function () {
    $enrollment = Enrollment::factory()->create([
        'programming_id' => $this->programming->id,
        'student_id' => $this->student->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.programmings.enrollments.toggle-status', [$this->programming, $enrollment]))
        ->assertRedirect();

    expect($enrollment->fresh()->is_active)->toBeFalse();
});

test('admin can import enrollments via excel', function () {
    Excel::fake();

    $this->actingAs($this->admin)
        ->post(route('admin.programmings.enrollments.import', $this->programming), [
            'file' => UploadedFile::fake()->create('enrollments.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ])
        ->assertRedirect();

    Excel::assertImported('enrollments.xlsx');
});

test('import enrollments fails without file', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.programmings.enrollments.import', $this->programming), [])
        ->assertSessionHasErrors('file');
});

test('guest cannot access enrollment store', function () {
    $this->post(route('admin.programmings.enrollments.store', $this->programming), [
        'student_id' => $this->student->id,
    ])->assertRedirect(route('login'));
});
