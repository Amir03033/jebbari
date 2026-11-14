@props(['items', 'name' => 'name', 'text' => 'description'])

{{-- A list of terms with a short explanation each: skills, current focus, tools. --}}
<dl {{ $attributes->class('def-list') }}>
    @foreach ($items as $item)
        <div class="def-row" data-reveal="group" data-delay="{{ min($loop->index, 4) }}">
            <span class="rule" aria-hidden="true"></span>
            <dt class="t-h3 rise">{{ loc($item[$name]) }}</dt>
            <dd class="rise text-muted" data-delay="1">{{ loc($item[$text] ?? null) }}</dd>
        </div>
    @endforeach
</dl>
