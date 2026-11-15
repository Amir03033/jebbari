<?php

namespace App\Scan;

use DOMDocument;
use DOMElement;
use DOMXPath;

/** A parsed HTML page with the few queries the checks need. */
final class Html
{
    private DOMXPath $xpath;

    public function __construct(string $html)
    {
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // The XML declaration makes libxml read the bytes as UTF-8.
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $this->xpath = new DOMXPath($doc);
    }

    /** @return list<DOMElement> */
    public function all(string $xpath): array
    {
        $nodes = $this->xpath->query($xpath);
        $out = [];

        foreach ($nodes ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $out[] = $node;
            }
        }

        return $out;
    }

    public function first(string $xpath): ?DOMElement
    {
        return $this->all($xpath)[0] ?? null;
    }

    public function text(string $xpath): ?string
    {
        $el = $this->first($xpath);

        return $el ? self::clean($el->textContent) : null;
    }

    /** Content of <meta name="..."> or <meta property="...">, case-insensitive. */
    public function meta(string $name): ?string
    {
        $name = strtolower($name);
        $el = $this->first("//meta[translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='{$name}' or translate(@property,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='{$name}']");

        return $el ? self::clean($el->getAttribute('content')) : null;
    }

    /** <link rel="..."> href, matching one token of the rel attribute. */
    public function link(string $rel): ?string
    {
        foreach ($this->all('//link[@rel]') as $el) {
            if (in_array(strtolower($rel), preg_split('/\s+/', strtolower(trim($el->getAttribute('rel')))), true)) {
                return trim($el->getAttribute('href')) ?: null;
            }
        }

        return null;
    }

    public static function clean(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }
}
