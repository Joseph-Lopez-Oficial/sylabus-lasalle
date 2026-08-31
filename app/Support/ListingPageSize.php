<?php

namespace App\Support;

/**
 * The page sizes a listing may be asked for.
 *
 * A closed list keeps a crafted request from pulling every row at once, which
 * with the history loaded would mean hundreds of records in a single page. It
 * lives outside the trait because a trait constant cannot be read from outside
 * the class that uses it, and both the interface and its tests need to know.
 */
final class ListingPageSize
{
    /** @var list<int> */
    public const ALLOWED = [15, 25, 50, 100];

    public const DEFAULT = 15;

    /** Resolves a requested size, falling back when it is not allowed. */
    public static function resolve(mixed $requested): int
    {
        return in_array((int) $requested, self::ALLOWED, true)
            ? (int) $requested
            : self::DEFAULT;
    }
}
