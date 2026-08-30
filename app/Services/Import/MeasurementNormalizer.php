<?php

namespace App\Services\Import;

/**
 * Normalises the free text the measurement spreadsheets carry.
 *
 * Every rule here is explicit: no fuzzy matching, no edit distance. The files
 * were filled in by hand over four periods, so the same professor, academic
 * space or student shows up written several ways, and guessing which ones mean
 * the same thing is exactly what must not happen silently.
 */
class MeasurementNormalizer
{
    /**
     * Files that are not measurements and must be skipped explicitly.
     *
     * @var list<string>
     */
    private const NON_MEASUREMENT_FILES = [
        'Informe_de_Competencias',
        'Matriz ajustes RA',
        'Mapa de RA',
        'Marco_competencias',
    ];

    /**
     * Performance level names as the spreadsheets write them, mapped to the
     * level order the system stores. The trailing score in parentheses is part
     * of the cell text, and "No Aplica" means the criterion was not assessed.
     *
     * @var array<string, int>
     */
    private const LEVEL_ORDER_BY_NAME = [
        'insuficiente' => 1,
        'basico' => 2,
        'satisfactorio' => 2,
        'bueno' => 3,
        'competente' => 3,
        'excelente' => 4,
        'destacado' => 4,
    ];

    /**
     * Declared equivalences, loaded once.
     *
     * @var array{academic_spaces: array<string, string|null>, professors: array<string, string>}|null
     */
    private ?array $aliases = null;

    /**
     * Catalogue name an academic space maps to.
     *
     * Returns null when the space is declared as not belonging to this
     * programme, and the original text when no equivalence applies.
     */
    public function academicSpace(string $value): ?string
    {
        $aliases = $this->aliases()['academic_spaces'];
        $key = $this->key($value);

        if (array_key_exists($key, $aliases)) {
            return $aliases[$key];
        }

        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Academic space guessed from the file name, for the file that leaves the
     * cell blank.
     *
     * The name is matched against the catalogue by comparison key, ignoring the
     * prefixes and the period the file names carry around it.
     */
    public function academicSpaceFromFileName(string $fileName): ?string
    {
        $name = pathinfo($fileName, PATHINFO_FILENAME);

        // Strip the parts that are never the space's name.
        $name = preg_replace('/^I?IAP_SOF_|^Seg_RA_/iu', '', $name);
        $name = preg_replace('/[_-]?(grupo|gr)\s*#?\s*[A-Za-z0-9]*.*$/iu', '', $name);

        // The journey belongs to the group, not to the space's name.
        $name = preg_replace('/[_ -]*(presencial|virtual|cd)$/iu', '', $name);

        // "HabilidadesInvestigativasI" carries no separators, so the comparison
        // key drops them on the catalogue side too.
        $key = str_replace(' ', '', $this->key($name));

        foreach ($this->catalogSpaceNames() as $candidate) {
            if (str_replace(' ', '', $this->key($candidate)) === $key) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function catalogSpaceNames(): array
    {
        $path = database_path('data/program-catalog.json');

        if (! is_file($path)) {
            return [];
        }

        $catalog = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return array_column($catalog['academic_spaces'] ?? [], 'name');
    }

    /**
     * Canonical spelling of a professor's name.
     */
    public function professor(string $value): string
    {
        $aliases = $this->aliases()['professors'];
        $key = $this->key($value);

        return $aliases[$key] ?? $this->personName($value);
    }

    /**
     * @return array{academic_spaces: array<string, string|null>, professors: array<string, string>}
     */
    private function aliases(): array
    {
        if ($this->aliases !== null) {
            return $this->aliases;
        }

        $path = database_path('data/import-aliases.json');

        if (! is_file($path)) {
            return $this->aliases = ['academic_spaces' => [], 'professors' => []];
        }

        $decoded = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $this->aliases = [
            'academic_spaces' => $decoded['academic_spaces'] ?? [],
            'professors' => $decoded['professors'] ?? [],
        ];
    }

    /**
     * Whether the file is a measurement spreadsheet at all.
     */
    public function isMeasurementFile(string $fileName): bool
    {
        foreach (self::NON_MEASUREMENT_FILES as $marker) {
            if (mb_stripos($fileName, $marker) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the file is an untouched copy of the blank template.
     */
    public function isBlankTemplate(string $fileName): bool
    {
        return mb_stripos($fileName, 'NombreEA') !== false;
    }

    /**
     * Comparison key: no accents, no case, no punctuation, single spaces.
     *
     * Used to decide whether two spellings point at the same catalogue entry.
     */
    public function key(string $value): string
    {
        $value = mb_strtolower(trim($value));

        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        ]);

        $value = preg_replace('/[^a-z0-9 ]+/u', ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Person name in a single canonical form: Title Case, single spaces.
     *
     * The order of surname and given name is deliberately NOT rearranged here:
     * the files use both orders and the document number is what identifies a
     * student, so choosing a display form is a separate decision.
     */
    public function personName(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value));

        return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Splits a full name into given names and surnames.
     *
     * Colombian names are usually two given names and two surnames. When the
     * name carries four or more words, the first half is treated as given names
     * and the second as surnames; with three words the split is 1/2, matching
     * the common "Nombre Apellido Apellido".
     *
     * @return array{0: string, 1: string} given names, surnames
     */
    public function splitName(string $value): array
    {
        $parts = preg_split('/\s+/u', $this->personName($value), -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === [] || $parts === false) {
            return ['', ''];
        }

        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $surnameCount = count($parts) >= 4 ? 2 : 1;
        $given = array_slice($parts, 0, count($parts) - $surnameCount);
        $surnames = array_slice($parts, -$surnameCount);

        return [implode(' ', $given), implode(' ', $surnames)];
    }

    /**
     * Document number stripped of dots, spaces and thousand separators.
     *
     * Returns null when the cell does not hold a document at all, which is how
     * header and total rows are told apart from students.
     */
    public function documentNumber(mixed $value): ?string
    {
        if (is_float($value) || is_int($value)) {
            $value = number_format((float) $value, 0, '.', '');
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        return preg_match('/^\d{5,}$/', (string) $digits) ? $digits : null;
    }

    /**
     * Translates a spreadsheet level such as "Excelente(4)" into the order of
     * the performance level it corresponds to.
     *
     * Returns null for "No Aplica" and for anything unrecognised, so the caller
     * records the criterion as not assessed instead of inventing a grade.
     */
    public function levelOrder(?string $value): ?int
    {
        $key = $this->key((string) $value);

        if ($key === '' || str_contains($key, 'no aplica')) {
            return null;
        }

        // The cell reads "excelente 4" once normalised; the leading word is the
        // level and the trailing digit is the score printed beside it.
        foreach (self::LEVEL_ORDER_BY_NAME as $name => $order) {
            if (str_starts_with($key, $name)) {
                return $order;
            }
        }

        return null;
    }

    /**
     * Reads a learning outcome statement such as
     * "RA42. Identifica conceptos básicos en redes. (Conocimiento)".
     *
     * The separator after the code is a dot in most rows and a comma in a few,
     * and the type in parentheses is what says which grading sheet holds it.
     *
     * @return array{code: string, description: string, type: string}|null
     */
    public function parseOutcome(?string $value): ?array
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^(RA\s*\d+)\s*[.,]?\s*(.*)$/u', $value, $matches)) {
            return null;
        }

        $rest = trim($matches[2]);
        $type = null;

        // The 2025-2 format states the type in a trailing parenthesis; the
        // 2025-1 one omits it, and the caller resolves it from the catalogue.
        if (preg_match('/^(.*?)\s*\(([^)]+)\)\s*$/u', $rest, $tail)) {
            $candidate = $this->outcomeType($tail[2]);

            if (in_array($candidate, ['Conocimiento', 'Habilidad', 'Actitud'], true)) {
                $rest = trim($tail[1]);
                $type = $candidate;
            }
        }

        return [
            'code' => str_replace(' ', '', $matches[1]),
            'description' => $rest,
            'type' => $type,
        ];
    }

    /**
     * Canonical name of an outcome type, since the files write "Conocimientos"
     * and "Actitudinal" alongside the singular forms the system stores.
     */
    public function outcomeType(string $value): string
    {
        $key = $this->key($value);

        return match (true) {
            str_starts_with($key, 'conocimiento') => 'Conocimiento',
            str_starts_with($key, 'habilidad') => 'Habilidad',
            str_starts_with($key, 'actitud') => 'Actitud',
            str_starts_with($key, 'conocimientos') => 'Conocimiento',
            default => $this->personName($value),
        };
    }

    /**
     * Group taken from the file name, which is reliable, falling back to the
     * cell only when the name says nothing.
     *
     * Several professors copied the template without updating `Consolidado!C11`,
     * so files belonging to different groups declare the same number. The name
     * keeps the journey suffix (1D, 1N, CD03) because that is what tells those
     * groups apart.
     */
    public function group(string $fileName, mixed $cellValue): ?string
    {
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $journey = $this->journey($name);

        if (preg_match('/grupo\s*#?\s*([A-Za-z0-9]{1,6})/iu', $name, $matches)) {
            $group = $this->cleanGroup($matches[1]);

            if ($group !== null) {
                return $this->withJourney($group, $journey);
            }
        }

        // Names such as "Lenguaje de programación2-GR01 2026-1".
        if (preg_match('/\bGR\s*([0-9]{1,3}[A-Za-z]?)\b/iu', $name, $matches)) {
            $group = $this->cleanGroup($matches[1]);

            if ($group !== null) {
                return $this->withJourney($group, $journey);
            }
        }

        $group = $this->cleanGroup((string) $cellValue);

        return $group === null ? null : $this->withJourney($group, $journey);
    }

    /**
     * Journey spelled out in the file name.
     *
     * The same professor teaching the same academic space runs a face-to-face
     * and a virtual group and numbers both "1", so without this they collapse
     * into one programming and their students get mixed together.
     */
    private function journey(string $fileName): ?string
    {
        $key = $this->key($fileName);

        return match (true) {
            str_contains($key, 'presencial') => 'P',
            str_contains($key, 'virtual') => 'V',
            // "CD" marks the distance-learning cohort, which runs as its own
            // group alongside the regular one under the same number.
            preg_match('/(^| )cd( |$)/u', $key) === 1 => 'CD',
            default => null,
        };
    }

    /**
     * Appends the journey unless the group code already carries it, so
     * "Grupo1V" and "Grupo1_Virtual" end up as the same group.
     */
    private function withJourney(string $group, ?string $journey): string
    {
        if ($journey === null || str_ends_with($group, $journey)) {
            return $group;
        }

        // A group already ending in a letter states its own journey.
        return preg_match('/[A-Z]$/u', $group) ? $group : $group.$journey;
    }

    /**
     * A group is a short code. Anything longer is a mis-filled cell, such as the
     * file that carries a competency statement where the group should be.
     */
    private function cleanGroup(string $value): ?string
    {
        $value = trim(preg_replace('/\s+/u', '', $value));

        if ($value === '' || mb_strlen($value) > 6) {
            return null;
        }

        $value = mb_strtoupper($value);

        // "01" and "1" are the same group written two ways.
        if (preg_match('/^0*(\d+)([A-Z]*)$/u', $value, $matches)) {
            return $matches[1].$matches[2];
        }

        return $value;
    }

    /**
     * Academic period as the system names it, read from "RESULTADO FINAL 2025-2"
     * or from the file name when the header is missing.
     */
    public function period(?string $headerValue, string $fileName): ?string
    {
        // The file name is read first, as with the group: at least one file
        // carries a mistyped period in its header ("2026-2" inside 2026-1)
        // while its name and folder agree with each other.
        // `\b` is useless here: file names separate words with underscores, and
        // an underscore is itself a word character, so the boundary never
        // matches. The delimiters are spelled out instead.
        foreach ([pathinfo($fileName, PATHINFO_FILENAME), (string) $headerValue] as $candidate) {
            if (preg_match('/(?<![0-9])(20\d{2})\s*-\s*([12])(?![0-9])/u', $candidate, $matches)) {
                return $matches[1].'-'.$matches[2];
            }

            // File names abbreviate the year: "25-2", "26-1".
            if (preg_match('/(?<![0-9])(\d{2})\s*-\s*([12])(?![0-9])/u', $candidate, $matches)) {
                return '20'.$matches[1].'-'.$matches[2];
            }
        }

        return null;
    }
}
