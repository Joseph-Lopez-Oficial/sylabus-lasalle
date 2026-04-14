// ── Pagination ────────────────────────────────────────────────────────────────

export type PaginatedResponse<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

// ── Catalog models ────────────────────────────────────────────────────────────

export type EvaluationCriterion = {
    id: number;
    name: string;
    description: string | null;
    order: number;
    created_at: string;
    updated_at: string;
};

export type PerformanceLevel = {
    id: number;
    name: string;
    description: string | null;
    order: number;
    created_at: string;
    updated_at: string;
};

export type Modality = {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
};

export type ActivityType = {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
};

export type MicrocurricularLearningOutcomeType = {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
};

// ── Academic structure ────────────────────────────────────────────────────────

export type Faculty = {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    programs?: Program[];
};

export type Program = {
    id: number;
    faculty_id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    faculty?: Faculty;
    problematic_nuclei?: ProblematicNucleus[];
};

export type ProblematicNucleus = {
    id: number;
    program_id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    program?: Program;
    competencies?: Competency[];
};

export type Competency = {
    id: number;
    problematic_nucleus_id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    problematic_nucleus?: ProblematicNucleus;
    mesocurricular_learning_outcomes?: MesocurricularLearningOutcome[];
    academic_spaces?: AcademicSpace[];
};

export type MesocurricularLearningOutcome = {
    id: number;
    competency_id: number;
    description: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    competency?: Competency;
    microcurricular_learning_outcomes?: MicrocurricularLearningOutcome[];
};

export type AcademicSpace = {
    id: number;
    competency_id: number;
    name: string;
    code: string;
    credits: number;
    semester: number | null;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    competency?: Competency;
    microcurricular_learning_outcomes?: MicrocurricularLearningOutcome[];
    topics?: Topic[];
    programmings?: Programming[];
};

export type MicrocurricularLearningOutcome = {
    id: number;
    academic_space_id: number;
    type_id: number;
    mesocurricular_learning_outcome_id: number | null;
    description: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    academic_space?: AcademicSpace;
    type?: MicrocurricularLearningOutcomeType;
    mesocurricular_learning_outcome?: MesocurricularLearningOutcome | null;
};

export type Topic = {
    id: number;
    academic_space_id: number;
    name: string;
    order: number;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    academic_space?: AcademicSpace;
    activities?: Activity[];
};

export type Activity = {
    id: number;
    topic_id: number;
    activity_type_id: number;
    name: string;
    description: string | null;
    order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    topic?: Topic;
    activity_type?: ActivityType;
    products?: Product[];
};

export type Product = {
    id: number;
    activity_id: number;
    name: string;
    description: string | null;
    order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    activity?: Activity;
};

// ── People ────────────────────────────────────────────────────────────────────

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    role: 'admin' | 'professor';
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    professor?: Professor;
    [key: string]: unknown;
};

export type Professor = {
    id: number;
    user_id: number | null;
    first_name: string;
    last_name: string;
    document_number: string;
    institutional_email: string;
    phone: string | null;
    is_active: boolean;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
    user?: User;
    programmings?: Programming[];
};

export type Student = {
    id: number;
    first_name: string;
    last_name: string;
    document_number: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
    enrollments?: Enrollment[];
};

// ── Programmings & Grading ────────────────────────────────────────────────────

export type Programming = {
    id: number;
    academic_space_id: number;
    professor_id: number;
    modality_id: number;
    period: string;
    group: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    academic_space?: AcademicSpace;
    professor?: Professor;
    modality?: Modality;
    enrollments?: Enrollment[];
};

export type Enrollment = {
    id: number;
    student_id: number;
    programming_id: number;
    enrolled_at: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    student?: Student;
    programming?: Programming;
    grades?: Grade[];
};

export type Grade = {
    id: number;
    enrollment_id: number;
    microcurricular_learning_outcome_id: number;
    evaluation_criterion_id: number;
    performance_level_id: number;
    graded_by: number;
    observations: string | null;
    graded_at: string;
    created_at: string;
    updated_at: string;
    enrollment?: Enrollment;
    microcurricular_learning_outcome?: MicrocurricularLearningOutcome;
    evaluation_criterion?: EvaluationCriterion;
    performance_level?: PerformanceLevel;
};

export type ImportLog = {
    id: number;
    imported_by: number;
    programming_id: number;
    file_name: string;
    total_rows: number;
    successful_rows: number;
    failed_rows: number;
    errors: { row: number; status: string; message: string }[] | null;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    imported_at: string | null;
    created_at: string;
    updated_at: string;
};

// ── Statistics ────────────────────────────────────────────────────────────────

export type StudentStats = {
    enrollment_id: number;
    student_id: number;
    student_name: string;
    final_average: number;
    totals_by_outcome: number[];
    by_criterion: {
        criterion_id: number;
        criterion_name: string;
        average: number;
    }[];
};

export type OutcomeStats = {
    outcome_id: number;
    outcome_desc: string;
    group_average: number;
    highest: number;
    lowest: number;
    distribution: {
        level_id: number;
        level_name: string;
        count: number;
        percentage: number;
    }[];
};

export type CriterionStats = {
    criterion_id: number;
    criterion_name: string;
    group_average: number;
};

export type ProgrammingStats = {
    byStudent: StudentStats[];
    byOutcome: OutcomeStats[];
    byCriterion: CriterionStats[];
    summary: {
        overall_average: number;
        distribution: {
            level_id: number;
            level_name: string;
            count: number;
            percentage: number;
        }[];
        top_students: StudentStats[];
        below_basic: StudentStats[];
    };
};
