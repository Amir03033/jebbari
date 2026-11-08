@php
    use App\Content\BlockRenderer;
    use App\Support\Locale;

    $hasIntro = BlockRenderer::blocks($page)->contains('type', 'intro');
    $start = $hasIntro ? 1 : 2;
    $next = $start + BlockRenderer::blocks($page, $start)->reject(fn ($b) => in_array($b['type'], ['meeting_line', 'reply_promise', 'image'], true))->count();
    $locale = app()->getLocale();
@endphp

<x-layouts.app :seo="$seo" :preview="$preview">
    @unless ($hasIntro)
        <x-page-header :page="$page" />
    @endunless
    <x-blocks :page="$page" :start="$start" />

    <section>
        <x-section-label :number="str_pad((string) $next, 2, '0', STR_PAD_LEFT)" :label="__('site.work.all')" :meta="'('.$projects->count().')'" />
        <div class="page py-16 md:py-28">
            @forelse ($projects as $project)
                @if ($loop->first)<div class="grid-12 gap-y-16 md:gap-y-24">@endif
                <x-plate
                    :class="$loop->first ? 'col-span-12' : 'col-span-12 md:col-span-6'"
                    :href="Locale::route('work.show', ['slug' => $project->slugFor($locale)])"
                    :number="str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT)"
                    :title="loc($project->title)"
                    :kind="loc($project->kind)"
                    :year="$project->year"
                    :stack="collect($project->stack)->take($loop->first ? 3 : 2)->implode(' · ')"
                    :summary="loc($project->summary)"
                    :asset="$project->cover"
                    :eager="$loop->first"
                    :sizes="$loop->first ? '(min-width: 1360px) 1264px, 100vw' : '(min-width: 1360px) 620px, (min-width: 768px) 50vw, 100vw'"
                />
                @if ($loop->last)</div>@endif
            @empty
                <p class="t-body text-muted">{{ __('site.empty.work') }}</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
