<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use AuditsChanges, HasPublishing, Translatable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quote' => 'array', 'is_featured' => 'boolean', 'permission_confirmed' => 'boolean'];
    }

    public function requiredTranslations(): array
    {
        return ['quote' => 'Citaat'];
    }

    /** A testimonial may only go live with the customer's permission. */
    public function publishIssues(): array
    {
        $issues = [];

        foreach ($this->translationGaps() as $locale => $labels) {
            foreach ($labels as $label) {
                $issues[] = strtoupper($locale).': '.$label.' ontbreekt';
            }
        }

        if (! $this->withDraft()->permission_confirmed) {
            $issues[] = 'Toestemming van de klant is niet bevestigd';
        }

        return $issues;
    }

    public static function previewType(): string
    {
        return 'testimonial';
    }
}
