<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\AcademicSpace;
use App\Models\Competency;
use App\Models\Faculty;
use App\Models\MicrocurricularLearningOutcomeType;
use App\Models\ProblematicNucleus;
use App\Models\Program;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Seeds the Software Engineering programme catalogue.
 *
 * The data comes from the "Datos" sheet the coordination ships inside every
 * measurement spreadsheet, extracted into `database/data/program-catalog.json`
 * so the catalogue is versioned as data instead of transcribed into code.
 *
 * The seeder is idempotent: entities are matched by their code, so running it
 * again refreshes descriptions without duplicating anything.
 */
class SoftwareEngineeringProgramSeeder extends Seeder
{
    /**
     * Academic periods covered by the delivered files. Only the current one is
     * left open; the rest are historical.
     */
    private const PERIODS = [
        ['name' => '2024-2', 'start_date' => '2024-07-15', 'end_date' => '2024-11-30', 'is_active' => false],
        ['name' => '2025-1', 'start_date' => '2025-01-20', 'end_date' => '2025-05-31', 'is_active' => false],
        ['name' => '2025-2', 'start_date' => '2025-07-14', 'end_date' => '2025-11-29', 'is_active' => false],
        ['name' => '2026-1', 'start_date' => '2026-01-19', 'end_date' => '2026-05-30', 'is_active' => true],
    ];

    public function run(): void
    {
        $catalog = $this->catalog();

        $faculty = Faculty::updateOrCreate(
            ['code' => 'FI'],
            ['name' => 'Facultad de Ingeniería', 'is_active' => true]
        );

        $program = Program::updateOrCreate(
            ['code' => 'SOF'],
            [
                'faculty_id' => $faculty->id,
                'name' => 'Ingeniería de Software',
                'is_active' => true,
            ]
        );

        // The spreadsheets carry a single emphasis area, which is the level the
        // academic hierarchy calls a problematic nucleus.
        $nucleus = ProblematicNucleus::updateOrCreate(
            ['program_id' => $program->id, 'name' => 'Innovación, Automatización y Productividad'],
            ['is_active' => true]
        );

        foreach (self::PERIODS as $period) {
            AcademicPeriod::updateOrCreate(['name' => $period['name']], $period);
        }

        $competencies = [];
        foreach ($catalog['competencies'] as $competency) {
            $competencies[$competency['code']] = Competency::updateOrCreate(
                ['code' => $competency['code'], 'problematic_nucleus_id' => $nucleus->id],
                [
                    'name' => $this->shortName($competency['description']),
                    'description' => $competency['description'],
                    'is_active' => true,
                ]
            );
        }

        // Every academic space hangs off the first competency: the spreadsheets
        // list which competencies a space contributes to, but the hierarchy
        // allows a single parent, and the measurement data does not depend on
        // that link.
        $defaultCompetency = reset($competencies);

        if (! $defaultCompetency) {
            throw new RuntimeException('El catálogo no trae competencias.');
        }

        $spaces = [];
        foreach ($catalog['academic_spaces'] as $space) {
            $spaces[$space['code']] = AcademicSpace::updateOrCreate(
                ['code' => $space['code']],
                [
                    'competency_id' => $defaultCompetency->id,
                    'name' => $space['name'],
                    'is_active' => true,
                ]
            );
        }

        if (MicrocurricularLearningOutcomeType::query()->doesntExist()) {
            throw new RuntimeException(
                'No hay tipos de resultado de aprendizaje sembrados. Ejecute MicrocurricularLearningOutcomeTypeSeeder primero.'
            );
        }

        // The learning outcomes are deliberately not created here. An outcome
        // belongs to an academic space, and the programme catalogue lists them
        // without that link: only the measurement files say which group
        // assessed which outcome, so the importer creates each one against the
        // space where it is actually used.
        $this->command?->info(sprintf(
            'Catálogo sembrado: %d competencias, %d espacios académicos. Los %d resultados de aprendizaje se asocian durante la importación.',
            count($competencies),
            count($spaces),
            count($catalog['outcomes'])
        ));
    }

    /**
     * @return array{competencies: list<array{code: string, description: string}>, academic_spaces: list<array{code: string, name: string}>, professors: list<string>, outcomes: list<array{code: string, description: string, type: string}>}
     */
    private function catalog(): array
    {
        $path = database_path('data/program-catalog.json');

        if (! is_file($path)) {
            throw new RuntimeException("No se encontró el catálogo del programa en {$path}.");
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * A competency's `name` column is short, while the catalogue only carries
     * the full statement, so the first sentence stands in as the name.
     */
    private function shortName(string $description): string
    {
        $firstSentence = preg_split('/(?<=\.)\s+/u', trim($description))[0] ?? $description;

        return mb_substr(trim($firstSentence), 0, 190);
    }
}
