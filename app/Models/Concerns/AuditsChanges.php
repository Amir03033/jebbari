<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** Records who changed what, through Spatie Activitylog. Drafts are logged by HasPublishing. */
trait AuditsChanges
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['draft_data', 'created_at', 'updated_at', 'deleted_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
