<?php

namespace App\Console\Commands;

use App\Support\PreviewUpdates;
use Illuminate\Console\Command;
use Throwable;

/**
 * The preview worker, for a Plesk scheduled task every minute: first checks
 * whether the live preview's branch has a new commit (automatic updates),
 * then works through the 'previews' queue and stops. See docs/PREVIEW-DEPLOY.md.
 */
class PreviewWork extends Command
{
    protected $signature = 'previews:work';

    protected $description = 'Check previews for new commits, then run the previews queue until it is empty';

    public function handle(PreviewUpdates $updates): int
    {
        try {
            if ($message = $updates->check()) {
                $this->info($message);
            }
        } catch (Throwable $e) {
            // An update check must never stop builds that are already queued.
            report($e);
        }

        return $this->call('queue:work', [
            'connection' => 'previews',
            '--queue' => 'previews',
            '--stop-when-empty' => true,
            '--max-time' => 55,
            // The builds run in their own process; this only guards the worker itself.
            '--memory' => 512,
        ]);
    }
}
