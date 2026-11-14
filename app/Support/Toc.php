<?php

namespace App\Support;

/**
 * A table of contents for an article: gives every h2 in already-sanitised HTML
 * an id and returns the list of headings to link to.
 */
class Toc
{
    /** @return array{html: string, items: list<array{id: string, text: string}>} */
    public static function build(string $html): array
    {
        $items = [];
        $used = [];

        $html = preg_replace_callback('/<h2>(.*?)<\/h2>/s', function (array $m) use (&$items, &$used) {
            $text = trim(html_entity_decode(strip_tags($m[1])));
            $base = str($text)->slug()->toString() ?: 'section';
            $id = $base;

            for ($n = 2; in_array($id, $used, true); $n++) {
                $id = $base.'-'.$n;
            }

            $used[] = $id;
            $items[] = ['id' => $id, 'text' => $text];

            return '<h2 id="'.e($id).'">'.$m[1].'</h2>';
        }, $html) ?? $html;

        return ['html' => $html, 'items' => $items];
    }
}
