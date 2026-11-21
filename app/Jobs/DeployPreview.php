<?php

namespace App\Jobs;

use App\Enums\PreviewStatus;
use App\Models\Preview;
use App\Support\PreviewServer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

/**
 * Builds one preview on the subdomain (scripts/preview-deploy.sh deploy) and
 * writes the output to its log while it runs. Runs on the 'previews' queue,
 * one at a time. See docs/PREVIEW-DEPLOY.md.
 */
class DeployPreview implements ShouldQueue
{
    use Queueable;

    /** Waiting for the lock counts as an attempt; a real error fails at once. */
    public int $tries = 60;

    public int $maxExceptions = 1;

    public int $timeout;

    public function __construct(public Preview $preview)
    {
        $this->onConnection('previews')->onQueue('previews');
        $this->timeout = (int) config('preview.timeout') + 60;
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('preview-server'))->shared()->releaseAfter(30)->expireAfter((int) config('preview.timeout') + 120)];
    }

    public function handle(PreviewServer $server): void
    {
        $preview = $this->preview->fresh();

        // Cleared or replaced while it waited.
        if (! $preview || $preview->status !== PreviewStatus::Pending) {
            return;
        }

        $preview->forceFill(['status' => PreviewStatus::Building, 'log' => '['.now()->timezone('Europe/Amsterdam')->format('d-m-Y H:i:s').'] Build started'.PHP_EOL])->saveQuietly();

        try {
            $server->assertConfigured();

            $buffer = '';
            $flushed = microtime(true);
            $exit = $server->run('deploy', $preview, function (string $chunk) use ($preview, &$buffer, &$flushed) {
                $buffer .= $chunk;
                if (microtime(true) - $flushed > 2) {
                    $preview->appendLog($buffer);
                    $buffer = '';
                    $flushed = microtime(true);
                }
            });
            $preview->appendLog($buffer);
        } catch (Throwable $e) {
            $this->finish($preview, PreviewStatus::Failed, 'Stopped: '.$e->getMessage());

            return;
        }

        $exit === 0
            ? $this->finish($preview, PreviewStatus::Ready, 'Ready.', ['deployed_at' => now(), 'approved_at' => null])
            : $this->finish($preview, PreviewStatus::Failed, 'The script stopped with exit code '.$exit.'.');
    }

    /** The worker gave up (timeout or crash): never leave it on "building". */
    public function failed(?Throwable $e): void
    {
        $preview = $this->preview->fresh();

        if ($preview && $preview->status->isBusy()) {
            $this->finish($preview, PreviewStatus::Failed, 'Stopped: '.($e?->getMessage() ?: 'the worker stopped the build.'));
        }
    }

    private function finish(Preview $preview, PreviewStatus $status, string $line, array $attributes = []): void
    {
        // "Preview wissen" during the build wins: the clear job runs right after this one.
        if ($preview->newQuery()->whereKey($preview->getKey())->where('status', PreviewStatus::Cleared)->exists()) {
            $status = PreviewStatus::Cleared;
            $line .= ' The preview was cleared in the meantime.';
            $attributes = [];
        }

        $preview->appendLog(PHP_EOL.'['.now()->timezone('Europe/Amsterdam')->format('d-m-Y H:i:s').'] '.$line.PHP_EOL);
        $preview->forceFill(['status' => $status] + $attributes)->saveQuietly();
    }
}
