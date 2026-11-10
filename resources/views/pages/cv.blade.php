@php
    use App\Content\BlockRenderer;
    use App\Content\Site;
    use App\Support\Locale;

    $intro = BlockRenderer::blocks($page)->firstWhere('type', 'intro');
    $email = Site::email();
    $locale = app()->getLocale();
@endphp

{{--
    The CV: assembled from the About page and the projects, laid out to print
    cleanly on one or two sheets ("Save as PDF" in the print dialog).
--}}
<x-layouts.app :seo="$seo" :preview="$preview">
    <x-page-opening number="01" :label="loc($intro['data']['label'] ?? null) ?: $title" :title="loc($intro['data']['heading'] ?? null) ?: $title" :lead="loc($intro['data']['body'] ?? null) ?: $about" />

    <div class="page cv-actions" data-reveal="up">
        <x-button type="button" data-print>{{ __('site.cv.print') }}<span class="arrow" aria-hidden="true">↓</span></x-button>
        <p class="text-sm text-muted">{{ __('site.cv.print_hint') }}</p>
    </div>

    @if ($experience->isNotEmpty())
        <section class="cv-section">
            <x-section-label number="02" :label="__('site.cv.experience')" />
            <div class="page section-body">
                <dl class="def-list">
                    @foreach ($experience as $item)
                        <div class="def-row" data-reveal="group">
                            <span class="rule" aria-hidden="true"></span>
                            <dt class="rise"><span class="t-label block text-muted">{{ $item['period'] ?? '' }}</span><span class="t-h3 mt-2 block">{{ loc($item['role']) }}@if (! empty($item['organisation'])), {{ $item['organisation'] }}@endif</span></dt>
                            <dd class="rise text-muted" data-delay="1">{{ loc($item['description'] ?? null) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    @endif

    @if ($skills->isNotEmpty())
        <section class="cv-section">
            <x-section-label number="03" :label="__('site.cv.skills')" />
            <div class="page section-body"><x-def-list :items="$skills" /></div>
        </section>
    @endif

    @if ($projects->isNotEmpty())
        <section class="cv-section">
            <x-section-label number="04" :label="__('site.cv.projects')" />
            <div class="page section-body">
                <dl class="def-list">
                    @foreach ($projects as $project)
                        <div class="def-row" data-reveal="group">
                            <span class="rule" aria-hidden="true"></span>
                            <dt class="rise"><a href="{{ Locale::route('work.show', ['slug' => $project->slugFor($locale)]) }}" class="link t-h3">{{ loc($project->title) }}</a><span class="t-label mt-2 block text-muted">{{ collect([loc($project->kind), $project->year])->filter()->implode(' · ') }}</span></dt>
                            <dd class="rise text-muted" data-delay="1">{{ loc($project->summary) }}@if ($project->stack)<span class="t-label mt-3 block">{{ implode(' · ', $project->stack) }}</span>@endif</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    @endif

    @if ($email)
        <section class="cv-section">
            <x-section-label number="05" :label="__('site.nav.contact')" />
            <div class="page section-body">
                <p><x-link href="mailto:{{ $email }}" class="cta-email">{{ $email }}</x-link></p>
                <p class="t-label mt-4 text-muted">{{ url('/') }}</p>
            </div>
        </section>
    @endif
</x-layouts.app>
