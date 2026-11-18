<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A newsletter written in the admin and sent once to every confirmed
 * subscriber, in their language (docs/NEWSLETTER.md). Locked after sending.
 */
class Newsletter extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['subject' => 'array', 'body' => 'array', 'sent_at' => 'datetime', 'recipients' => 'integer'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    /** Subject and body in this language, falling back to Dutch when it was not written. */
    public function in(string $locale): array
    {
        $pick = fn (?array $v) => filled($v[$locale] ?? null) ? $v[$locale] : ($v['nl'] ?? '');

        return ['subject' => (string) $pick($this->subject), 'body' => (string) $pick($this->body)];
    }
}
