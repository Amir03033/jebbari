@php
    use App\Support\Locale;

    $locale = app()->getLocale();
    $stack = collect($project->stack ?? []);
    $meta = collect([
        [__('site.project.category'), loc($project->kind)],
        [__('site.project.year'), $project->year],
        [__('site.project.client'), $project->client_name],
    ])->filter(fn ($r) => filled($r[1]));
    $brief = loc($project->brief);
    $built = loc($project->built);
    $outcome = loc($project->outcome);
    $contact = \App\Content\Site::pageUrl('contact');
@endphp

<x-layouts.app :seo="$seo" :preview="$preview">
    <article>
        <x-section-label number="01" :label="__('site.nav.work')" :meta="loc($project->kind)" />

        <header class="page pb-10 pt-12 md:pb-16 md:pt-20" data-reveal="group">
            <nav aria-label="{{ __('site.breadcrumb') }}" class="t-label rise mb-8 text-muted">
                <a href="{{ Locale::url('work') }}" class="link link-bare">← {{ __('site.nav.work') }}</a>
            </nav>
            <h1 class="t-display md:max-w-[90%]"><x-lines :lines="[$title]" /></h1>
            @if (loc($project->summary))
                <div class="grid-12 mt-10 md:mt-16">
                    <p class="t-body rise col-span-12 text-muted md:col-span-5 md:col-start-8" data-delay="5">{{ loc($project->summary) }}</p>
                </div>
            @endif
        </header>

        @if ($project->cover)
            <div class="page pb-16 md:pb-28" data-reveal="group">
                <div class="plate-frame"><div class="plate-clip"><div class="plate-reveal"><x-picture :asset="$project->cover" sizes="(min-width: 1360px) 1264px, 100vw" :eager="true" /></div></div></div>
            </div>
        @endif

        <div class="page pb-16 md:pb-28">
            <div class="grid-12 gap-y-12">
                @if ($meta->isNotEmpty() || $stack->isNotEmpty() || $project->url)
                    <dl class="col-span-12 grid gap-6 self-start md:col-span-4">
                        @foreach ($meta as [$term, $value])
                            <div class="border-t border-line pt-3"><dt class="t-label text-muted">{{ $term }}</dt><dd class="mt-1 tabular-nums">{{ $value }}</dd></div>
                        @endforeach
                        @if ($stack->isNotEmpty())
                            <div class="border-t border-line pt-3">
                                <dt class="t-label text-muted">{{ __('site.project.stack') }}</dt>
                                <dd class="mt-2 flex flex-wrap gap-x-4 gap-y-1"><ul class="t-label flex flex-wrap gap-x-4 gap-y-1">@foreach ($stack as $tech)<li>{{ $tech }}</li>@endforeach</ul></dd>
                            </div>
                        @endif
                        @if ($project->url)
                            <div class="border-t border-line pt-3"><dt class="t-label text-muted">{{ __('site.project.visit') }}</dt><dd class="mt-1"><x-link :href="$project->url" rel="noopener" target="_blank" arrow>{{ parse_url($project->url, PHP_URL_HOST) }}</x-link></dd></div>
                        @endif
                    </dl>
                @endif

                <div class="col-span-12 md:col-span-7 md:col-start-6">
                    @if ($brief)
                        <h2 class="t-label mb-3 text-muted">{{ __('site.project.brief') }}</h2>
                        <p class="t-body mb-12 text-ink">{{ $brief }}</p>
                    @endif
                    @if ($built)
                        <h2 class="t-label mb-3 text-muted">{{ __('site.project.built') }}</h2>
                        <x-rich-text :html="$built" class="mb-12" />
                    @endif
                    @if ($outcome)
                        <h2 class="t-label mb-3 text-muted">{{ __('site.project.outcome') }}</h2>
                        <p class="t-h2">{{ $outcome }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if ($gallery->isNotEmpty())
            <section aria-label="{{ __('site.project.gallery') }}">
                <x-section-label number="02" :label="__('site.project.gallery')" :meta="'('.$gallery->count().')'" />
                <div class="page grid gap-12 py-16 md:gap-20 md:py-28">
                    @foreach ($gallery as $item)
                        <figure data-reveal="group">
                            <div class="plate-frame"><div class="plate-clip"><div class="plate-reveal"><x-picture :asset="$item['asset']" sizes="(min-width: 1360px) 1264px, 100vw" /></div></div></div>
                            @if ($item['caption'])<figcaption class="t-label mt-3 text-muted">{{ $item['caption'] }}</figcaption>@endif
                        </figure>
                    @endforeach
                </div>
            </section>
        @endif
    </article>

    @if ($related->isNotEmpty())
        <section>
            <x-section-label :number="$gallery->isNotEmpty() ? '03' : '02'" :label="__('site.project.related')" />
            <div class="page py-16 md:py-28">
                <div class="grid-12 gap-y-16">
                    @foreach ($related as $item)
                        <x-plate
                            class="col-span-12 md:col-span-6"
                            :href="Locale::route('work.show', ['slug' => $item->slugFor($locale)])"
                            :number="str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT)"
                            :title="loc($item->title)" :kind="loc($item->kind)" :year="$item->year"
                            :stack="collect($item->stack)->take(3)->implode(' · ')" :summary="loc($item->summary)" :asset="$item->cover"
                        />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($contact)
        <section class="on-ink">
            <x-section-label number="—" :label="__('site.nav.contact')" />
            <div class="page py-16 md:py-28" data-reveal="group">
                <h2 class="t-h2 max-w-[18ch]"><x-lines :lines="[__('site.project.cta')]" /></h2>
                <p class="rise mt-8" data-delay="3"><x-button :href="$contact">{{ __('site.cta.contact') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
            </div>
        </section>
    @endif
</x-layouts.app>
