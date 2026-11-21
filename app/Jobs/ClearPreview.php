<?php

namespace App\Jobs;

use App\Support\PreviewServer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Empties the subdomain (a neutral placeholder page stays) and drops every
 * table in the preview database. The preview records were already marked cleared.
 */
class ClearPreview implements ShouldQueue
{
    use Queueable;

    public int $tries = 60;

    public int $maxExceptions = 1;

    public int $timeout = 600;

    public function __construct()
    {
        $this->onConnection('previews')->onQueue('previews');
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('preview-server'))->shared()->releaseAfter(30)->expireAfter((int) config('preview.timeout') + 120)];
    }

    public function handle(PreviewServer $server): void
    {
        $server->assertConfigured();

        $output = '';
        $exit = $server->run('clear', null, function (string $chunk) use (&$output) {
            $output .= $chunk;
        });

        if ($exit !== 0) {
            throw new RuntimeException('Clearing the preview failed: '.trim($output));
        }

        try {
            Schema::connection('preview')->dropAllTables();
        } finally {
            DB::purge('preview');
        }

        Log::info('Preview cleared');
    }
}
