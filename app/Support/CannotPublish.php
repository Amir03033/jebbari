<?php

namespace App\Support;

use RuntimeException;

class CannotPublish extends RuntimeException
{
    /** @param list<string> $issues */
    public function __construct(public readonly array $issues)
    {
        parent::__construct('Publiceren is geblokkeerd: '.implode('; ', $issues));
    }
}
