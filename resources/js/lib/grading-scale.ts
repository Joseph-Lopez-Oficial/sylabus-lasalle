import type { ScaleLevel } from '@/types';

/**
 * Palette applied by position in the scale, from lowest to highest level.
 *
 * Colours are assigned by position rather than by name, so renaming a level
 * from the administration screen does not leave it without a colour.
 */
const RAMP = ['#ef4444', '#f97316', '#22c55e', '#3b82f6', '#8b5cf6'];

const FALLBACK_COLOR = '#94a3b8';

/**
 * Scale in force for the page currently rendered.
 *
 * The statistics pages label grades deep inside nested components that do not
 * receive the scale as a prop. Rather than thread it through every level, the
 * page sets it once on mount and the helpers below read it from here.
 */
let activeScale: ScaleLevel[] = [];

/** Called by each page with the scale the backend sent. */
export function setActiveScale(scale: ScaleLevel[] | undefined): void {
    activeScale = scale ?? [];
}

/** Levels that carry a value, ordered from highest to lowest. */
function valuedLevelsDescending(scale: ScaleLevel[] = activeScale): ScaleLevel[] {
    return scale
        .filter((level) => level.grade_value !== null)
        .sort((a, b) => (b.grade_value ?? 0) - (a.grade_value ?? 0));
}

/** Colour of a level, resolved by its position within the configured scale. */
export function levelColor(
    levelName: string,
    scale: ScaleLevel[] = activeScale,
): string {
    const index = scale.findIndex((level) => level.name === levelName);

    if (index === -1) return FALLBACK_COLOR;

    // The top level always takes the last colour of the ramp, so a shorter
    // scale still ends on the same tone.
    if (index === scale.length - 1) {
        return RAMP[RAMP.length - 1] ?? FALLBACK_COLOR;
    }

    return RAMP[index] ?? FALLBACK_COLOR;
}

/**
 * Name of the level a grade falls into, according to the configured scale.
 *
 * Returns the highest level whose value the grade reaches. Levels without a
 * value take no part, since they define no boundary.
 */
export function levelLabelForGrade(
    grade: number,
    scale: ScaleLevel[] = activeScale,
): string {
    const valued = valuedLevelsDescending(scale);
    const match = valued.find((level) => grade >= (level.grade_value ?? 0));

    return match?.name ?? valued[valued.length - 1]?.name ?? '—';
}

/** Grade under which a student is considered at risk. */
export function belowBasicThreshold(scale: ScaleLevel[] = activeScale): number {
    return scale.find((level) => level.is_below_basic_threshold)?.grade_value ?? 0;
}

/** Name of the level that defines the at-risk boundary. */
export function belowBasicLevelName(
    scale: ScaleLevel[] = activeScale,
): string {
    return scale.find((level) => level.is_below_basic_threshold)?.name ?? '—';
}

/** Position of a grade in the scale: 0 is the top level. */
function levelIndexForGrade(grade: number, scale: ScaleLevel[] = activeScale): number {
    return valuedLevelsDescending(scale).findIndex(
        (level) => grade >= (level.grade_value ?? 0),
    );
}

/** Text colour for a grade, resolved from the configured scale. */
export function gradeTextClass(
    grade: number,
    scale: ScaleLevel[] = activeScale,
): string {
    const index = levelIndexForGrade(grade, scale);

    if (index === 0) return 'text-blue-600 dark:text-blue-400';
    if (index === 1) return 'text-green-600 dark:text-green-400';
    if (index > 1) return 'text-orange-500 dark:text-orange-400';

    return 'text-red-600 dark:text-red-400';
}

/**
 * Badge classes for a level, resolved by its name.
 *
 * Used where the level is already known, so its position in the scale decides
 * the tone without going through a grade value.
 */
export function levelBadgeClass(
    levelName: string,
    scale: ScaleLevel[] = activeScale,
): string {
    const value = scale.find((level) => level.name === levelName)?.grade_value;

    return value === null || value === undefined
        ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
        : gradeBadgeClass(value, scale);
}

/** Badge classes for a grade, resolved from the configured scale. */
export function gradeBadgeClass(
    grade: number,
    scale: ScaleLevel[] = activeScale,
): string {
    const index = levelIndexForGrade(grade, scale);

    if (index === 0) {
        return 'bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    }
    if (index === 1) {
        return 'bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-300';
    }
    if (index > 1) {
        return 'bg-orange-50 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300';
    }

    return 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-300';
}
