<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as Sanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Rich text from the CMS is reduced to a small, safe set of tags before it is
 * saved and again before it is rendered. No scripts, iframes, styles or classes.
 */
class HtmlSanitizer
{
    private static ?Sanitizer $instance = null;

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return self::instance()->sanitize($html);
    }

    private static function instance(): Sanitizer
    {
        return self::$instance ??= new Sanitizer(
            (new HtmlSanitizerConfig)
                ->allowElement('p')
                ->allowElement('br')
                ->allowElement('h2')
                ->allowElement('h3')
                ->allowElement('strong')
                ->allowElement('em')
                ->allowElement('ul')
                ->allowElement('ol')
                ->allowElement('li')
                ->allowElement('blockquote')
                ->allowElement('a', ['href'])
                ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
                ->allowRelativeLinks()
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
        );
    }
}
