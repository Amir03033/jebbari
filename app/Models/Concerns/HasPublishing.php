<?php

namespace App\Models\Concerns;

use App\Enums\PublishStatus;
use App\Support\CannotPublish;
use App\Support\ContentCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * Draft / publish workflow shared by every content type.
 *
 * - A new record is a draft and is edited directly.
 * - Once published, the live columns are never touched by editing. Form saves go
 *   into `draft_data` (only the fields that differ) and the record shows
 *   "unpublished changes". publish() applies them; discardDraft() drops them.
 * - A future `published_at` means scheduled: the record goes live by itself.
 */
trait HasPublishing
{
    /** Attributes that are workflow state, never part of a draft. */
    private const WORKFLOW = ['id', 'status', 'published_at', 'draft_data', 'created_at', 'updated_at', 'deleted_at'];

    public function initializeHasPublishing(): void
    {
        $this->mergeCasts([
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
            'draft_data' => 'array',
        ]);

        // New records are drafts from the first moment, not only after a reload.
        $this->attributes['status'] ??= PublishStatus::Draft->value;
    }

    // ---------------------------------------------------------------- state

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published->value)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function isPublished(): bool
    {
        return $this->status === PublishStatus::Published;
    }

    public function isLive(): bool
    {
        return $this->isPublished() && ($this->published_at === null || $this->published_at->isPast());
    }

    public function isScheduled(): bool
    {
        return $this->isPublished() && $this->published_at?->isFuture();
    }

    public function hasDraftChanges(): bool
    {
        return filled($this->draft_data);
    }

    /** draft | scheduled | live | live_changes */
    public function workflowState(): string
    {
        if (! $this->isPublished()) {
            return 'draft';
        }

        if ($this->isScheduled()) {
            return 'scheduled';
        }

        return $this->hasDraftChanges() ? 'live_changes' : 'live';
    }

    // ------------------------------------------------------------- drafting

    /** A copy of this record with the unpublished changes applied. Never saved. */
    public function withDraft(): static
    {
        $copy = $this->newInstance([], $this->exists);
        $copy->setRawAttributes($this->getAttributes(), true);
        $copy->forceFill($this->draft_data ?? []);

        return $copy;
    }

    /** Attributes an editor can change through the form (everything except workflow state). */
    public function draftableAttributes(): array
    {
        return array_values(array_diff(array_keys($this->getAttributes()), self::WORKFLOW));
    }

    /**
     * Persist form data respecting the workflow. This is the only way the
     * admin writes content, so live content cannot be changed by accident.
     */
    public function saveFromForm(array $data): void
    {
        $data = Arr::except($data, self::WORKFLOW);

        if (! $this->isPublished()) {
            $this->fill($data);
            $this->save();

            return;
        }

        $changes = [];

        foreach ($data as $key => $value) {
            if (self::canonical($this->getAttribute($key)) !== self::canonical($value)) {
                $changes[$key] = $value;
            }
        }

        $hadDraft = $this->hasDraftChanges();
        $this->draft_data = $changes ?: null;
        $this->saveQuietly();

        if ($changes || $hadDraft) {
            $this->logWorkflow('draft_saved', ['fields' => array_keys($changes)]);
        }
    }

    // ------------------------------------------------------------- publish

    /** @return list<string> reasons publishing is blocked (empty when ready) */
    public function publishIssues(): array
    {
        $issues = [];

        if (method_exists($this, 'translationGaps')) {
            foreach ($this->translationGaps() as $locale => $labels) {
                foreach ($labels as $label) {
                    $issues[] = strtoupper($locale).': '.$label.' ontbreekt';
                }
            }
        }

        if (method_exists($this, 'mediaIssues')) {
            $issues = [...$issues, ...$this->withDraft()->mediaIssues()];
        }

        return [...$issues, ...$this->placeholderIssues()];
    }

    /**
     * Content that still contains an owner placeholder such as "[[ OWNER: KvK ]]"
     * must not go live. Placeholders are for the editor, never for visitors.
     *
     * @return list<string>
     */
    public function placeholderIssues(): array
    {
        $copy = $this->withDraft();
        $json = json_encode(collect($copy->getAttributes())->except(self::WORKFLOW)->map(fn ($v, $k) => $copy->getAttribute($k))->all(), JSON_UNESCAPED_UNICODE);

        return str_contains((string) $json, '[[ OWNER') ? ['De tekst bevat nog een invulveld "[[ OWNER: ... ]]"'] : [];
    }

    public function publish(): void
    {
        $issues = $this->publishIssues();

        if ($issues) {
            throw new CannotPublish($issues);
        }

        $wasPublished = $this->isPublished();

        DB::transaction(function () {
            $this->forceFill($this->draft_data ?? []);
            $this->draft_data = null;
            $this->status = PublishStatus::Published;
            $this->published_at ??= now();
            $this->save();
        });

        $this->logWorkflow($wasPublished ? 'published_changes' : 'published');
        ContentCache::bump();
    }

    public function unpublish(): void
    {
        $this->status = PublishStatus::Draft;
        $this->saveQuietly();
        $this->logWorkflow('unpublished');
        ContentCache::bump();
    }

    public function discardDraft(): void
    {
        $this->draft_data = null;
        $this->saveQuietly();
        $this->logWorkflow('draft_discarded');
    }

    // -------------------------------------------------------------- preview

    public function previewUrl(string $locale = 'nl'): string
    {
        return URL::temporarySignedRoute('preview', now()->addMinutes(30), [
            'type' => static::previewType(),
            'id' => $this->getKey(),
            'lang' => $locale,
        ]);
    }

    abstract public static function previewType(): string;

    // -------------------------------------------------------------- helpers

    protected function logWorkflow(string $event, array $properties = []): void
    {
        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->event($event)
            ->withProperties($properties)
            ->log($event);
    }

    /** Order-independent, empty-tolerant form for comparing field values. */
    public static function canonical(mixed $value): string
    {
        $normalise = function ($v) use (&$normalise) {
            if (is_array($v)) {
                $v = array_map($normalise, $v);
                $v = array_filter($v, fn ($x) => $x !== null && $x !== '' && $x !== []);
                if (! array_is_list($v)) {
                    ksort($v);
                }

                return $v;
            }

            return $v instanceof \BackedEnum ? $v->value : (is_bool($v) ? (int) $v : (is_numeric($v) ? (string) $v : $v));
        };

        $v = $normalise($value);

        return json_encode($v === '' || $v === [] ? null : $v);
    }
}
