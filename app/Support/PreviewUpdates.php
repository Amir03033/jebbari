<?php

namespace App\Support;

use App\Enums\PreviewStatus;
use App\Models\Preview;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Automatic updates: when the branch of the live preview has a new commit on
 * GitHub, the preview is deployed again (keeping its database). Checked every
 * minute by `php artisan previews:work`, the preview worker's scheduled task.
 */
class PreviewUpdates
{
    /** @return string|null what happened, for the worker's output */
    public function check(): ?string
    {
        $preview = Preview::query()
            ->whereIn('status', [PreviewStatus::Ready, PreviewStatus::Failed])
            ->where('auto_update', true)
            ->latest('updated_at')
            ->first();

        if (! $preview || $preview->isExpired() || Preview::busy()) {
            return null;
        }

        $latest = $this->latestCommit($preview);

        // Unknown, or already built (or tried): nothing to do. A commit that
        // failed is not retried; the next push is.
        if ($latest === null || $latest === $preview->commit_sha) {
            return null;
        }

        $preview->forceFill(['commit_sha' => $latest])->saveQuietly();
        $preview->deploy();

        return 'New commit '.substr($latest, 0, 7).' on '.$preview->branch.': preview "'.$preview->client_name.'" queued.';
    }

    /**
     * The "Bijwerken" button: deploy this preview again if its branch has a
     * commit that is not live yet.
     *
     * @return array{0: string, 1: string} [result, message] with result
     *                                     'queued', 'current', 'busy' or 'unknown'
     */
    public function update(Preview $preview): array
    {
        if (Preview::busy()) {
            return ['busy', 'Er loopt al een build. Wacht tot die klaar is.'];
        }

        $latest = $this->latestCommit($preview);

        if ($latest === null) {
            return ['unknown', 'GitHub gaf geen antwoord over branch "'.$preview->branch.'". Controleer de repository, de branch en PREVIEW_GITHUB_TOKEN.'];
        }

        if ($latest === $preview->commit_sha && $preview->status === PreviewStatus::Ready) {
            return ['current', 'Al bijgewerkt: de nieuwste commit ('.substr($latest, 0, 7).') staat live.'];
        }

        $from = $preview->commit_sha ? substr($preview->commit_sha, 0, 7) : 'onbekend';
        $preview->forceFill(['commit_sha' => $latest])->saveQuietly();
        $preview->deploy();

        return ['queued', 'Nieuwe versie gevonden ('.$from.' → '.substr($latest, 0, 7).'). De build start binnen een minuut.'];
    }

    /** The full hash of the branch's newest commit, or null if GitHub cannot tell. */
    public function latestCommit(Preview $preview): ?string
    {
        $path = $preview->repoPath();

        if (! $path || ! Preview::validBranch($preview->branch)) {
            return null;
        }

        try {
            $request = Http::timeout(10)->withHeaders([
                'Accept' => 'application/vnd.github.sha',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'jebbari-preview',
            ]);

            if ($token = config('preview.github_token')) {
                $request = $request->withToken($token);
            }

            $response = $request->get('https://api.github.com/repos/'.$path.'/commits/'.$preview->branch);
        } catch (Throwable $e) {
            Log::warning('Preview update check failed', ['message' => $e->getMessage()]);

            return null;
        }

        $sha = trim($response->body());

        return $response->successful() && preg_match('/^[0-9a-f]{40}$/', $sha) ? $sha : null;
    }
}
