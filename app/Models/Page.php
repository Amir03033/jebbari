<?php

namespace App\Models;

use App\Content\Blocks\Blocks;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\ReferencesMedia;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

/**
 * A system page (home, about, services, work, contact, blog index) or a legal
 * page. Its content is an ordered list of typed blocks: [{id, type, data}].
 */
class Page extends Model
{
    use AuditsChanges, HasPublishing, ReferencesMedia, Translatable;

    protected $guarded = ['id'];

    /** System pages keep their slugs in code; only legal pages have slugs here. */
    public function slugSource(): ?string
    {
        return $this->kind === 'legal' ? 'title' : null;
    }

    protected function casts(): array
    {
        return ['title' => 'array', 'blocks' => 'array', 'seo' => 'array'];
    }

    public function requiredTranslations(): array
    {
        return ['title' => 'Titel'];
    }

    /**
     * Whether the page itself may be shown in a language: only its title counts.
     * A block that lacks text in that language is left out of the page instead of
     * taking the whole page down (publishing already requires complete blocks).
     */
    public function isTranslatedIn(string $locale): bool
    {
        return filled(loc($this->title, $locale));
    }

    /** Pages whose opening is a black stage; the header then starts dark too. */
    public function hasDarkOpening(): bool
    {
        return in_array($this->key, ['home', 'work', 'services', 'contact'], true);
    }

    /** @return list<string> problems inside visible blocks, for one locale */
    public function blockGaps(string $locale): array
    {
        $gaps = [];

        foreach ($this->blocks ?? [] as $block) {
            $definition = Blocks::find($this->key, $block['type'] ?? '');

            if (! $definition || ($block['data']['visible'] ?? true) === false) {
                continue;
            }

            $gaps = [...$gaps, ...$definition->gaps($block['data'] ?? [], $locale)];
        }

        return $gaps;
    }

    public function mediaIds(): array
    {
        $ids = [$this->getAttribute('seo')['og_media_id'] ?? null];

        foreach ($this->blocks ?? [] as $block) {
            $definition = Blocks::find($this->key, $block['type'] ?? '');
            if ($definition && ($block['data']['visible'] ?? true) !== false) {
                $ids = [...$ids, ...$definition->mediaIds($block['data'] ?? [])];
            }
        }

        return $ids;
    }

    public function publishIssues(): array
    {
        $issues = [];

        foreach ($this->withDraft()->blocks ?? [] as $block) {
            $definition = Blocks::find($this->key, $block['type'] ?? '');
            if ($definition && ($block['data']['visible'] ?? true) !== false) {
                $issues = [...$issues, ...$definition->issues($block['data'] ?? [])];
            }
        }

        return [...$this->publishIssuesBase(), ...$issues, ...$this->placeholderIssues()];
    }

    private function publishIssuesBase(): array
    {
        $issues = [];

        foreach ($this->translationGaps() as $locale => $labels) {
            foreach ($labels as $label) {
                $issues[] = strtoupper($locale).': '.$label.' ontbreekt';
            }
        }

        return [...$issues, ...$this->withDraft()->mediaIssues()];
    }

    public static function previewType(): string
    {
        return 'page';
    }
}
