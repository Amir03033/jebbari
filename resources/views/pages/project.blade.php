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
    $timeline = collect($project->timeline ?? [])->filter(fn ($i) => loc($i['title'] ?? null))->values();
    $changelog = collect($project->changelog ?? [])->filter(fn ($i) => loc($i['notes'] ?? null))->values();
    $uptime = \App\Uptime\Monitor::summary($project);

    // Section numbers for what is present, computed once (component props can be evaluated twice).
    $present = array_keys(array_filter([
        'overview' => $summary || $brief,
        'what' => $built || $features || $objectives,
        'interface' => $gallery->isNotEmpty(),
        'compare' => $project->before && $project->after,
        'demo' => (bool) $project->demo_url,
        'progress' => $timeline->isNotEmpty() || $changelog->isNotEmpty(),
        'outcome' => (bool) $outcome,
    ]));
    $no = fn (string $key) => str_pad((string) (array_search($key, $present, true) + 2), 2, '0', STR_PAD_LEFT);
@endphp

<x-layouts.app :seo="$seo" :preview="$preview">
    {{--
        A case study, kept calm: the name and the cover, one row of facts, the
        story, what it does, the screens, how it went, and the result. One idea
        per section, no decorative cards.
    --}}
    <article>
        <x-page-opening number="01" :label="__('site.nav.work')" :title="$title"
            :back="Locale::url('work')" :back-label="__('site.nav.work')" :meta="collect([loc($project->kind), $project->year])->filter()->implode(' · ')" />

        <div class="page-wide case-cover" data-reveal="group" data-scroll="enter">
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

        {{-- The facts in one quiet row under the cover. --}}
        @if ($meta->isNotEmpty() || $stack->isNotEmpty() || $project->url || $uptime)
            <div class="page">
                <dl class="case-facts" data-reveal="group">
                    @foreach ($meta as [$term, $value])
                        <div class="rise"><dt class="t-label text-muted">{{ $term }}</dt><dd>{{ $value }}</dd></div>
                    @endforeach
                    @if ($project->url)
                        <div class="rise"><dt class="t-label text-muted">{{ __('site.project.visit') }}</dt><dd><x-link :href="$project->url" rel="noopener" target="_blank" arrow>{{ parse_url($project->url, PHP_URL_HOST) }}</x-link></dd></div>
                    @endif
                    @if ($uptime)
                        <div class="rise">
                            <dt class="t-label text-muted">{{ __('site.project.uptime_label') }}</dt>
                            <dd>
                                @if ($uptime['online'] !== null)
                                    <span class="uptime-now" data-ok="{{ $uptime['online'] ? 'true' : 'false' }}"><span class="uptime-dot" aria-hidden="true"></span>{{ $uptime['online'] ? __('site.project.uptime_online') : __('site.project.uptime_offline') }}</span>
                                @endif
                                <span class="block tabular-nums">{{ __('site.project.uptime_value', ['uptime' => number_format($uptime['uptime'], 2, $locale === 'nl' ? ',' : '.', '')]) }}{{ $uptime['ms'] ? ' · '.__('site.project.uptime_ms', ['ms' => $uptime['ms']]) : '' }}</span>
                                <span class="mt-1 block text-sm text-muted">{{ __('site.project.uptime_note', ['checks' => number_format($uptime['checks'], 0, ',', '.')]) }}</span>
                            </dd>
                        </div>
                    @endif
                    @if ($stack->isNotEmpty())
                        <div class="case-facts-wide rise">
                            <dt class="t-label text-muted">{{ __('site.project.stack') }}</dt>
                            <dd><ul class="t-label mt-1 flex flex-wrap gap-2">@foreach ($stack as $tech)<li class="chip">{{ $tech }}</li>@endforeach</ul></dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif

        {{-- The story: what it is and what was asked, in one readable column. --}}
        @if ($summary || $brief)
            <section>
                <x-section-label :number="$no('overview')" :label="__('site.project.overview')" />
                <div class="page section-body">
                    <div class="grid-12" data-reveal="group">
                        <div class="col-span-12 md:col-span-9 lg:col-span-8 lg:col-start-5">
                            @if ($summary)<p class="t-statement rise">{{ $summary }}</p>@endif
                            @if ($brief)<p class="t-body rise mt-8 max-w-[62ch] text-muted" data-delay="2">{{ $brief }}</p>@endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- What it does: how it was built, the goals and the features, as plain lists. --}}
        @if ($built || $features || $objectives)
            <section>
                <x-section-label :number="$no('what')" :label="__('site.project.what')" />
                <div class="page section-body">
                    @if ($built)
                        <div class="grid-12 mb-14 md:mb-20" data-reveal="group">
                            <div class="col-span-12 md:col-span-9 lg:col-span-8 lg:col-start-5"><x-rich-text :html="$built" class="rise" /></div>
                        </div>
                    @endif
                    <div class="grid-12 gap-y-12">
                        @if ($objectives)
                            <div @class(['col-span-12', 'lg:col-span-4' => $features])>
                                <h3 class="t-label text-muted">{{ __('site.project.objectives') }}</h3>
                                <ul class="case-list case-goals mt-4" data-reveal="group">
                                    @foreach ($objectives as $goal)<li class="rise">{{ $goal }}</li>@endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($features)
                            <div @class(['col-span-12', 'lg:col-span-7 lg:col-start-6' => $objectives])>
                                <h3 class="t-label text-muted">{{ __('site.project.features') }}</h3>
                                <ol class="case-list case-features mt-4" data-reveal="group">
                                    @foreach ($features as $feature)
                                        <li class="rise"><span class="t-label text-muted">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span>{{ $feature }}</span></li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- The screens: the first wide, the rest two by two. --}}
        @if ($gallery->isNotEmpty())
            <section aria-label="{{ __('site.project.interface') }}">
                <x-section-label :number="$no('interface')" :label="__('site.project.interface')" :meta="'('.$gallery->count().')'" />
                <div class="page section-body">
                    <div class="grid-12 gap-y-10 md:gap-x-10 md:gap-y-14">
                        @foreach ($gallery as $item)
                            @php($wide = $loop->first || ($loop->last && $loop->count % 2 === 0))
                            <figure @class(['col-span-12', 'md:col-span-6' => ! $wide]) data-reveal="group">
                                <div class="plate-frame" data-ratio="{{ $wide ? 'wide' : 'standard' }}">
                                    <div class="plate-clip"><div class="plate-reveal"><x-picture :asset="$item['asset']" :sizes="$wide ? '(min-width: 1360px) 1264px, 100vw' : '(min-width: 768px) 50vw, 100vw'" /></div></div>
                                </div>
                                @if ($item['caption'])<figcaption class="t-label mt-4 text-muted">{{ $item['caption'] }}</figcaption>@endif
                            </figure>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Before and after: one frame, a handle to drag between the two. --}}
        @if ($project->before && $project->after)
            <section>
                <x-section-label :number="$no('compare')" :label="__('site.project.compare')" />
                <div class="page section-body" data-reveal="group">
                    <div class="compare plate-frame rise" data-ratio="wide" data-compare>
                        <div class="compare-layer"><x-picture :asset="$project->after" sizes="(min-width: 1360px) 1264px, 100vw" /></div>
                        <div class="compare-layer compare-before"><x-picture :asset="$project->before" sizes="(min-width: 1360px) 1264px, 100vw" /></div>
                        <span class="compare-tag compare-tag-before t-label">{{ __('site.project.before') }}</span>
                        <span class="compare-tag compare-tag-after t-label">{{ __('site.project.after') }}</span>
                        <span class="compare-handle" aria-hidden="true"><span></span></span>
                        <input type="range" class="compare-range" min="0" max="100" value="50" aria-label="{{ __('site.project.compare_label') }}">
                    </div>
                </div>
            </section>
        @endif

        {{-- Live demo: the real product in a frame, loaded only when asked for. --}}
        @if ($project->demo_url)
            <section>
                <x-section-label :number="$no('demo')" :label="__('site.project.demo')" />
                <div class="page section-body" data-reveal="group">
                    <div class="demo rise" data-demo="{{ $project->demo_url }}" data-demo-title="{{ $title }}">
                        <div class="demo-bar t-label text-muted">
                            <span>{{ parse_url($project->demo_url, PHP_URL_HOST) }}</span>
                            <x-link :href="$project->demo_url" rel="noopener" target="_blank" arrow>{{ __('site.project.demo_open') }}</x-link>
                        </div>
                        <div class="demo-stage">
                            <div class="demo-cover">
                                <p class="max-w-[36ch] text-muted">{{ __('site.project.demo_note') }}</p>
                                <x-button type="button" data-demo-load>{{ __('site.project.demo_load') }}<span class="arrow" aria-hidden="true">→</span></x-button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- How it went: the milestones, with the release notes folded away. --}}
        @if ($timeline->isNotEmpty() || $changelog->isNotEmpty())
            <section>
                <x-section-label :number="$no('progress')" :label="__('site.project.progress')" />
                <div class="page section-body">
                    @if ($timeline->isNotEmpty())
                        <ol class="case-steps" data-reveal="group">
                            @foreach ($timeline as $item)
                                <li class="rise" data-delay="{{ min($loop->index, 4) }}">
                                    <span class="t-label text-muted">{{ ($item['date'] ?? '') ?: str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <div>
                                        <h3 class="case-step-title">{{ loc($item['title']) }}</h3>
                                        @if (loc($item['text'] ?? null))<p class="mt-1 max-w-[56ch] text-muted">{{ loc($item['text']) }}</p>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                    @if ($changelog->isNotEmpty())
                        <details @class(['case-releases', 'mt-10' => $timeline->isNotEmpty()])>
                            <summary class="t-label">{{ __('site.project.releases', ['count' => $changelog->count()]) }}</summary>
                            <dl class="mt-6">
                                @foreach ($changelog as $release)
                                    <div class="case-release">
                                        <dt><span class="font-medium">{{ $release['version'] ?? '' }}</span> <span class="t-label text-muted">{{ $release['date'] ?? '' }}</span></dt>
                                        <dd><ul class="changelog-notes">@foreach (PackagePricing::lines(loc($release['notes'])) as $note)<li>{{ $note }}</li>@endforeach</ul></dd>
                                    </div>
                                @endforeach
                            </dl>
                        </details>
                    @endif
                </div>
            </section>
        @endif

        {{-- The result: one honest sentence, given room. --}}
        @if ($outcome)
            <section class="section-tight">
                <div class="outcome panel on-ink">
                    <span class="cta-glow" aria-hidden="true"></span>
                    <div class="page-wide relative py-16 md:py-24" data-reveal="group">
                        <p class="t-label label-row"><span class="text-muted">{{ $no('outcome') }}</span> / {{ __('site.project.outcome') }}</p>
                        <p class="t-h2 mt-6 md:mt-8 md:max-w-[28ch]"><x-lines :lines="[$outcome]" /></p>
                    </div>
                </div>
            </section>
        @endif

        <div class="page pb-16 md:pb-24" data-reveal="up"><x-share :url="$seo['canonical']" :title="$title" /></div>
    </article>

    {{-- The next project closes the page; it replaces a separate list of related work. --}}
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
