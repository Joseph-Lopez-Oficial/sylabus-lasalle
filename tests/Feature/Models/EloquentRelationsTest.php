<?php

namespace Tests\Feature\Models;

use App\Models\AcademicSpace;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Competency;
use App\Models\Enrollment;
use App\Models\EvaluationCriterion;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\ImportLog;
use App\Models\MesocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcome;
use App\Models\MicrocurricularLearningOutcomeType;
use App\Models\Modality;
use App\Models\PerformanceLevel;
use App\Models\ProblematicNucleus;
use App\Models\Product;
use App\Models\Professor;
use App\Models\Program;
use App\Models\Programming;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_has_many_programs(): void
    {
        $faculty = Faculty::factory()->create();
        Program::factory()->count(3)->create(['faculty_id' => $faculty->id]);

        expect($faculty->programs)->toHaveCount(3);
        expect($faculty->programs->first())->toBeInstanceOf(Program::class);
    }

    public function test_program_belongs_to_faculty(): void
    {
        $faculty = Faculty::factory()->create();
        $program = Program::factory()->create(['faculty_id' => $faculty->id]);

        expect($program->faculty)->toBeInstanceOf(Faculty::class);
        expect($program->faculty->id)->toBe($faculty->id);
    }

    public function test_program_has_many_problematic_nuclei(): void
    {
        $program = Program::factory()->create();
        ProblematicNucleus::factory()->count(2)->create(['program_id' => $program->id]);

        expect($program->problematicNuclei)->toHaveCount(2);
    }

    public function test_problematic_nucleus_belongs_to_program(): void
    {
        $program = Program::factory()->create();
        $nucleus = ProblematicNucleus::factory()->create(['program_id' => $program->id]);

        expect($nucleus->program)->toBeInstanceOf(Program::class);
        expect($nucleus->program->id)->toBe($program->id);
    }

    public function test_problematic_nucleus_has_many_competencies(): void
    {
        $nucleus = ProblematicNucleus::factory()->create();
        Competency::factory()->count(2)->create(['problematic_nucleus_id' => $nucleus->id]);

        expect($nucleus->competencies)->toHaveCount(2);
    }

    public function test_competency_belongs_to_problematic_nucleus(): void
    {
        $nucleus = ProblematicNucleus::factory()->create();
        $competency = Competency::factory()->create(['problematic_nucleus_id' => $nucleus->id]);

        expect($competency->problematicNucleus)->toBeInstanceOf(ProblematicNucleus::class);
        expect($competency->problematicNucleus->id)->toBe($nucleus->id);
    }

    public function test_competency_has_many_mesocurricular_outcomes(): void
    {
        $competency = Competency::factory()->create();
        MesocurricularLearningOutcome::factory()->count(2)->create(['competency_id' => $competency->id]);

        expect($competency->mesocurricularLearningOutcomes)->toHaveCount(2);
    }

    public function test_competency_has_many_academic_spaces(): void
    {
        $competency = Competency::factory()->create();
        AcademicSpace::factory()->count(2)->create(['competency_id' => $competency->id]);

        expect($competency->academicSpaces)->toHaveCount(2);
    }

    public function test_academic_space_has_many_microcurricular_outcomes(): void
    {
        $space = AcademicSpace::factory()->create();
        MicrocurricularLearningOutcome::factory()->count(3)->create(['academic_space_id' => $space->id]);

        expect($space->microcurricularLearningOutcomes)->toHaveCount(3);
    }

    public function test_academic_space_has_many_topics(): void
    {
        $space = AcademicSpace::factory()->create();
        \App\Models\Topic::factory()->count(4)->create(['academic_space_id' => $space->id]);

        expect($space->topics)->toHaveCount(4);
    }

    public function test_microcurricular_outcome_belongs_to_type(): void
    {
        $type = MicrocurricularLearningOutcomeType::factory()->create();
        $outcome = MicrocurricularLearningOutcome::factory()->create(['type_id' => $type->id]);

        expect($outcome->type)->toBeInstanceOf(MicrocurricularLearningOutcomeType::class);
        expect($outcome->type->id)->toBe($type->id);
    }

    public function test_microcurricular_outcome_belongs_to_meso_outcome(): void
    {
        $meso = MesocurricularLearningOutcome::factory()->create();
        $outcome = MicrocurricularLearningOutcome::factory()->create([
            'mesocurricular_learning_outcome_id' => $meso->id,
        ]);

        expect($outcome->mesocurricularLearningOutcome)->toBeInstanceOf(MesocurricularLearningOutcome::class);
        expect($outcome->mesocurricularLearningOutcome->id)->toBe($meso->id);
    }

    public function test_topic_has_many_activities(): void
    {
        $activityType = ActivityType::factory()->create();
        $topic = \App\Models\Topic::factory()->create();
        Activity::factory()->count(2)->create([
            'topic_id' => $topic->id,
            'activity_type_id' => $activityType->id,
        ]);

        expect($topic->activities)->toHaveCount(2);
    }

    public function test_activity_has_many_products(): void
    {
        $activity = Activity::factory()->create();
        Product::factory()->count(3)->create(['activity_id' => $activity->id]);

        expect($activity->products)->toHaveCount(3);
    }

    public function test_professor_belongs_to_user(): void
    {
        $user = User::factory()->create(['role' => 'professor']);
        $professor = Professor::factory()->create(['user_id' => $user->id]);

        expect($professor->user)->toBeInstanceOf(User::class);
        expect($professor->user->id)->toBe($user->id);
    }

    public function test_user_has_one_professor(): void
    {
        $user = User::factory()->create(['role' => 'professor']);
        $professor = Professor::factory()->create(['user_id' => $user->id]);

        expect($user->professor)->toBeInstanceOf(Professor::class);
        expect($user->professor->id)->toBe($professor->id);
    }

    public function test_programming_belongs_to_professor_and_academic_space(): void
    {
        $professor = Professor::factory()->create();
        $space = AcademicSpace::factory()->create();
        $modality = Modality::factory()->create();
        $programming = Programming::factory()->create([
            'professor_id' => $professor->id,
            'academic_space_id' => $space->id,
            'modality_id' => $modality->id,
        ]);

        expect($programming->professor->id)->toBe($professor->id);
        expect($programming->academicSpace->id)->toBe($space->id);
        expect($programming->modality->id)->toBe($modality->id);
    }

    public function test_professor_has_many_programmings(): void
    {
        $professor = Professor::factory()->create();
        Programming::factory()->count(2)->create(['professor_id' => $professor->id]);

        expect($professor->programmings)->toHaveCount(2);
    }

    public function test_student_has_many_enrollments(): void
    {
        $student = Student::factory()->create();
        Enrollment::factory()->count(2)->create(['student_id' => $student->id]);

        expect($student->enrollments)->toHaveCount(2);
    }

    public function test_enrollment_belongs_to_student_and_programming(): void
    {
        $student = Student::factory()->create();
        $programming = Programming::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'programming_id' => $programming->id,
        ]);

        expect($enrollment->student->id)->toBe($student->id);
        expect($enrollment->programming->id)->toBe($programming->id);
    }

    public function test_grade_belongs_to_enrollment_outcome_criterion_level_and_user(): void
    {
        $enrollment = Enrollment::factory()->create();
        $outcome = MicrocurricularLearningOutcome::factory()->create();
        $criterion = EvaluationCriterion::factory()->create();
        $level = PerformanceLevel::factory()->create();
        $user = User::factory()->admin()->create();

        $grade = Grade::factory()->create([
            'enrollment_id' => $enrollment->id,
            'microcurricular_learning_outcome_id' => $outcome->id,
            'evaluation_criterion_id' => $criterion->id,
            'performance_level_id' => $level->id,
            'graded_by' => $user->id,
        ]);

        expect($grade->enrollment->id)->toBe($enrollment->id);
        expect($grade->microcurricularLearningOutcome->id)->toBe($outcome->id);
        expect($grade->evaluationCriterion->id)->toBe($criterion->id);
        expect($grade->performanceLevel->id)->toBe($level->id);
        expect($grade->gradedBy->id)->toBe($user->id);
    }

    public function test_import_log_belongs_to_user_and_programming(): void
    {
        $user = User::factory()->create();
        $programming = Programming::factory()->create();
        $log = ImportLog::factory()->create([
            'imported_by' => $user->id,
            'programming_id' => $programming->id,
        ]);

        expect($log->importedBy->id)->toBe($user->id);
        expect($log->programming->id)->toBe($programming->id);
    }

    public function test_faculty_active_scope_filters_inactive(): void
    {
        Faculty::factory()->count(3)->create(['is_active' => true]);
        Faculty::factory()->count(2)->inactive()->create();

        expect(Faculty::active()->count())->toBe(3);
    }

    public function test_student_soft_deletes(): void
    {
        $student = Student::factory()->create();
        $student->delete();

        expect(Student::count())->toBe(0);
        expect(Student::withTrashed()->count())->toBe(1);
    }

    public function test_professor_soft_deletes(): void
    {
        $professor = Professor::factory()->create();
        $professor->delete();

        expect(Professor::count())->toBe(0);
        expect(Professor::withTrashed()->count())->toBe(1);
    }
}
