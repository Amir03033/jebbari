<?php

namespace App\Scan;

use RuntimeException;

/** A URL the scanner refuses to fetch. The reason is a key in lang scan.error. */
class UnsafeUrl extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('Unsafe URL: '.$reason);
    }
}
