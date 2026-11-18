<?php

namespace App\Leads;

use RuntimeException;

/** The chosen slot is no longer free (someone else was faster, or it passed). */
class SlotTaken extends RuntimeException {}
