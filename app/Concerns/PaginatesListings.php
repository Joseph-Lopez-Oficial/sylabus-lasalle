<?php

namespace App\Concerns;

use App\Support\ListingPageSize;

trait PaginatesListings
{
    /**
     * How many records the current request wants per page.
     *
     * Anything outside the allowed sizes falls back to the default rather than
     * failing, since a bad page size is not worth an error page.
     */
    protected function perPage(): int
    {
        return ListingPageSize::resolve(request('per_page'));
    }
}
