<?php

namespace App\Scan;

use RuntimeException;

/** The page could not be fetched. The reason is a key in lang scan.error. */
class FetchFailed extends RuntimeException
{
    public function __construct(public readonly string $reason, ?\Throwable $previous = null)
    {
        parent::__construct('Fetch failed: '.$reason, 0, $previous);
    }
}
