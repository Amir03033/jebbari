@props(['page', 'start' => 1, 'only' => null, 'except' => []])

{{-- Renders a page's visible blocks in the order set in the CMS. Layout is fixed per block type. --}}
@foreach (\App\Content\BlockRenderer::blocks($page, $start) as $block)
    @continue(($only !== null && ! in_array($block['type'], $only, true)) || in_array($block['type'], $except, true))
    <x-dynamic-component :component="'blocks.'.$block['type']" :data="$block['data']" :page="$page" :number="$block['number']" />
@endforeach
