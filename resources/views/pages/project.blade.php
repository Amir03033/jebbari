@php
    use App\Content\PackagePricing;
    use App\Content\Site;
    use App\Support\Locale;

    $locale = app()->getLocale();
    $stack = collect($project->stack ?? []);
    $meta = collect([
        [__('site.project.category'), loc($project->kind)],
        [__('site.project.year'), $project->year],
        [__('site.project.client'), $project->client_name],
    ])->filter(fn ($r) => filled($r[1]));
    $summary = loc($project->summary);
    $brief = loc($project->brief);
    $objectives = PackagePricing::lines(loc($project->objectives));
    $built = loc($project->built);
    $features = PackagePricing::lines(loc($project->features));
    $outcome = loc($project->outcome);
    $contact = Site::pageUrl('contact');
    $hasOverview = $summary || $brief || $meta->isNotEmpty() || $stack->isNotEmpty() || $project->url;
    $n = 1;
    $number = function () use (&$n) {
        return str_pad((string) ++$n, 2, '0', STR_PAD_LEFT);
    };
    $galleryLayout = ['md:col-span-12', 'md:col-span-6', 'md:col-span-6 md:mt-24', 'md:col-span-8 md:col-start-4', 'md:col-span-7', 'md:col-span-5 md:mt-20'];
@endphp

<x-layouts.app :seo="$seo" :preview="$preview">
    <article>
        {{-- 1. The opening: the name, large, then the cover at the full width of the page. --}}
        <x-page-opening number="01" :label="__('site.nav.work')" :title="$title"
            :back="Locale::url('work')" :back-label="__('site.nav.work')" :meta="collect([loc($project->kind), $project->year])->filter()->implode(' · ')" />

        <div class="page-wide case-cover" data-reveal="group">
            <div class="plate-frame" data-ratio="wide">
                <div class="plate-clip"><div class="plate-reveal"><div class="plate-img">
                    @if ($project->cover)
                        <x-picture :asset="$project->cover" sizes="100vw" :eager="true" />
                    @else
                        <x-tile :title="$title" :kind="loc($project->kind)" :large="true" />
                    @endif
                </div></div></div>
            </div>
        </div>

        {{-- 2. Overview: what it is, in a large voice, with the facts beside it. --}}
        @if ($hasOverview)
            <section>
                <x-section-label :number="$number()" :label="__('site.project.overview')" />
                <div class="page py-16 md:py-28">
                    <div class="grid-12 gap-y-12" data-reveal="group">
                        <div class="col-span-12 md:col-span-8 md:col-start-5 md:order-2">
                            @if ($summary)<p class="t-statement rise">{{ $summary }}</p>@endif
                            @if ($brief)<p class="t-body rise mt-8 text-muted" data-delay="2">{{ $brief }}</p>@endif
                        </div>
                        <dl class="col-span-12 grid content-start gap-6 md:col-span-4 md:order-1">
                            @foreach ($meta as [$term, $value])
                                <div class="rise border-t border-line pt-3"><dt class="t-label text-muted">{{ $term }}</dt><dd class="mt-1 tabular-nums">{{ $value }}</dd></div>
                            @endforeach
                            @if ($stack->isNotEmpty())
                                <div class="rise border-t border-line pt-3">
                                    <dt class="t-label text-muted">{{ __('site.project.stack') }}</dt>
                                    <dd class="mt-3"><ul class="t-label flex flex-wrap gap-2">@foreach ($stack as $tech)<li class="chip">{{ $tech }}</li>@endforeach</ul></dd>
                                </div>
                            @endif
                            @if ($project->url)
                                <div class="rise border-t border-line pt-3"><dt class="t-label text-muted">{{ __('site.project.visit') }}</dt><dd class="mt-1"><x-link :href="$project->url" rel="noopener" target="_blank" arrow>{{ parse_url($project->url, PHP_URL_HOST) }}</x-link></dd></div>
                            @endif
                        </dl>
                    </div>
                </div>
            </section>
        @endif

        {{-- 3. Objectives. --}}
        @if ($objectives)
            <x-statement-list :items="$objectives" :number="$number()" :label="__('site.project.objectives')" />
        @endif

        {{-- 4. Features and implementation: the story of what was built, and what it does. --}}
        @if ($built || $features)
            <section class="bg-paper-2">
                <x-section-label :number="$number()" :label="__('site.project.features')" />
                <div class="page py-16 md:py-28">
                    <div class="grid-12 gap-y-12" data-reveal="group">
                        @if ($built)
                            <div class="col-span-12 md:col-span-5"><x-rich-text :html="$built" class="rise" /></div>
                        @endif
                        @if ($features)
                            <ol @class(['col-span-12 grid gap-4 sm:grid-cols-2', 'md:col-span-7' => $built, 'md:col-span-12 lg:grid-cols-3' => ! $built])>
                                @foreach ($features as $feature)
                                    <li class="feature-card rise" data-delay="{{ min($loop->index, 4) }}">
                                        <span class="t-label text-muted">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <p class="t-h3 mt-6">{{ $feature }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- 5. Interface: the screenshots, composed rather than stacked. --}}
        @if ($gallery->isNotEmpty())
            <section aria-label="{{ __('site.project.interface') }}">
                <x-section-label :number="$number()" :label="__('site.project.interface')" :meta="'('.$gallery->count().')'" />
                <div class="page py-16 md:py-28">
                    <div class="grid-12 gap-y-12 md:gap-y-20">
                        @foreach ($gallery as $item)
                            <figure @class(['col-span-12', $galleryLayout[$loop->index % count($galleryLayout)]]) data-reveal="group">
                                <div class="plate-frame" data-ratio="{{ in_array($loop->index % count($galleryLayout), [5], true) ? 'tall' : 'wide' }}">
                                    <div class="plate-clip"><div class="plate-reveal"><x-picture :asset="$item['asset']" sizes="(min-width: 1360px) 1264px, 100vw" /></div></div>
                                </div>
                                @if ($item['caption'])<figcaption class="t-label mt-4 text-muted">{{ $item['caption'] }}</figcaption>@endif
                            </figure>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- 6. Outcome: only when the owner wrote one. --}}
        @if ($outcome)
            <section class="section-tight">
                <div class="outcome panel on-ink">
                    <span class="cta-glow" aria-hidden="true"></span>
                    <div class="page-wide relative py-20 md:py-36" data-reveal="group">
                        <p class="t-label label-row"><span class="text-muted">{{ $number() }}</span> / {{ __('site.project.outcome') }}</p>
                        <p class="t-display mt-8 md:mt-12 md:max-w-[92%]"><x-lines :lines="[$outcome]" /></p>
                    </div>
                </div>
            </section>
        @endif

        {{-- 7. Related work. --}}
        @if ($related->isNotEmpty())
            <section>
                <x-section-label :number="$number()" :label="__('site.project.related')" />
                <div class="page py-16 md:py-28">
                    <div class="grid-12 gap-y-16">
                        @foreach ($related as $item)
                            <x-plate
                                class="col-span-12 md:col-span-6"
                                :href="Locale::route('work.show', ['slug' => $item->slugFor($locale)])"
                                :number="str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT)"
                                :title="loc($item->title)" :kind="loc($item->kind)" :year="$item->year"
                                :stack="collect($item->stack)->take(2)->implode(' · ')" :summary="loc($item->summary)" :asset="$item->cover"
                            />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </article>

    {{-- 8. Closing navigation: the next project, as large as the page is wide. --}}
    @if ($next)
        <div class="cta-wrap">
            <a href="{{ Locale::route('work.show', ['slug' => $next->slugFor($locale)]) }}" class="next-panel panel on-ink" data-cursor="{{ __('site.cta.view_case') }}" data-scroll="enter">
                <span class="cta-grid" aria-hidden="true"></span>
                <span class="cta-glow" aria-hidden="true"></span>
                <span class="page-wide relative block" data-reveal="group">
                    <span class="t-label label-row block"><span class="text-muted">→</span> / {{ __('site.project.next') }}</span>
                    <span class="mt-8 flex flex-wrap items-end justify-between gap-10 md:mt-12">
                        <span class="next-title max-w-[14ch]"><x-lines :lines="[loc($next->title)]" /></span>
                        <x-arrow-ring class="size-16 md:size-24" />
                    </span>
                </span>
            </a>
        </div>
    @elseif ($contact)
        <x-cta-panel :lines="[__('site.project.cta')]">
            <x-button :href="$contact">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button>
        </x-cta-panel>
    @endif
</x-layouts.app>
