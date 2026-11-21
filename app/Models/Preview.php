<?php

namespace App\Models;

use App\Enums\PreviewStatus;
use App\Jobs\ClearPreview;
use App\Jobs\DeployPreview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A client website deployed to the preview subdomain. Only one is live at a
 * time; older ones are marked cleared. See docs/PREVIEW-DEPLOY.md.
 */
class Preview extends Model
{
    /** https://github.com/<owner>/<repo>, optionally with .git or a trailing slash. */
    public const REPO_PATTERN = '#^https://github\.com/([A-Za-z0-9](?:[A-Za-z0-9-]{0,38}))/([A-Za-z0-9._-]{1,100}?)(?:\.git)?/?$#';

    /** Letters, digits, . _ / -; not starting with - or /, no "..", "//", "@{" or trailing "/" or ".lock". */
    public const BRANCH_PATTERN = '#^(?![-/.])(?!.*\.\.)(?!.*//)(?!.*\.lock$)(?!.*/$)[A-Za-z0-9._/-]{1,100}$#';

    protected $guarded = ['id'];

    protected $hidden = ['token', 'access_key'];

    protected function casts(): array
    {
        return [
            'status' => PreviewStatus::class,
            'dev_dependencies' => 'boolean',
            'keep_database' => 'boolean',
            'auto_update' => 'boolean',
            'owns_database' => 'boolean',
            'deployed_at' => 'datetime',
            'approved_at' => 'datetime',
            'change_requested_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Preview $preview) {
            $preview->token ??= Str::random(64);
            $preview->access_key ??= Str::random(64);
            $preview->status ??= PreviewStatus::Pending;
            $preview->expires_at ??= now()->addDays(config('preview.lifetime_days'));
        });
    }

    /** The canonical clone URL (https://github.com/owner/repo.git), or null if the input is not a GitHub repository URL. */
    public static function normalizeRepoUrl(?string $url): ?string
    {
        if (! is_string($url) || ! preg_match(self::REPO_PATTERN, trim($url), $m)) {
            return null;
        }

        [, $owner, $repo] = $m;

        if ($repo === '.' || $repo === '..' || str_starts_with($repo, '-')) {
            return null;
        }

        return 'https://github.com/'.$owner.'/'.$repo.'.git';
    }

    public static function validBranch(?string $branch): bool
    {
        return is_string($branch) && preg_match(self::BRANCH_PATTERN, $branch) === 1;
    }

    /** A build is queued or running somewhere. Only one at a time. */
    public static function busy(): bool
    {
        return static::query()->whereIn('status', [PreviewStatus::Pending, PreviewStatus::Building])->exists();
    }

    /**
     * Queues a build of this preview. It becomes the only one: every other
     * preview is marked cleared, so their client links stop working. Each
     * preview has its own access key, so their cookies no longer open the
     * subdomain either. The key stays the same across redeploys of one
     * preview, so a client who is looking keeps access after an update.
     */
    public function deploy(): void
    {
        static::query()->whereKeyNot($this->getKey())->where('status', '!=', PreviewStatus::Cleared)
            ->update(['status' => PreviewStatus::Cleared]);

        $this->forceFill(['status' => PreviewStatus::Pending, 'log' => null])->save();

        DeployPreview::dispatch($this);
    }

    /** Empties the subdomain and its database; every client link stops working. */
    public static function clearServer(): void
    {
        static::query()->where('status', '!=', PreviewStatus::Cleared)->update(['status' => PreviewStatus::Cleared]);
        static::query()->update(['owns_database' => false]);

        ClearPreview::dispatch();
    }

    /** The next build keeps the database only if asked and if it still holds this preview's data. */
    public function keepsDatabase(): bool
    {
        return $this->keep_database && $this->owns_database;
    }

    /** owner/repo, from the stored clone URL. */
    public function repoPath(): ?string
    {
        return preg_match(self::REPO_PATTERN, (string) $this->repo_url, $m) ? $m[1].'/'.$m[2] : null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** The client can see it and respond. */
    public function isViewable(): bool
    {
        return $this->status === PreviewStatus::Ready && ! $this->isExpired();
    }

    public function changesSentForThisBuild(): bool
    {
        return $this->change_requested_at !== null
            && ($this->deployed_at === null || $this->change_requested_at->gte($this->deployed_at));
    }

    public function reviewUrl(): string
    {
        return route('review.show', $this->token);
    }

    public function appendLog(string $text): void
    {
        $this->log = ($this->log ?? '').$text;
        $this->saveQuietly();
    }
}
