@props(['data', 'page', 'number'])

@php
    use App\Content\Site;
    use App\Support\Locale;

    $mode = $data['mode'] ?? 'featured';
    $projects = Site::projects($mode === 'featured', $mode === 'selected' ? ($data['project_ids'] ?? []) : null)->take(max(1, min(4, (int) ($data['limit'] ?? 4))));
    $workIndex = Site::pageUrl('work');
    $locale = app()->getLocale();
    $count = $projects->count();
@endphp

<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" :meta="'('.$count.')'" />
    <div class="page pb-10 pt-20 md:pb-16 md:pt-36">
        <div class="grid-12 gap-y-6" data-reveal="group">
            <h2 class="t-display col-span-12"><x-lines :lines="[loc($data['label'] ?? null)]" /></h2>
            @if (loc($data['intro'] ?? null))
                <p class="t-statement rise col-span-12 max-w-[30ch] text-muted md:col-span-6 md:col-start-7" data-delay="4" data-fill>{{ loc($data['intro']) }}</p>
            @endif
        </div>
    </div>

    @if ($count > 1)
        {{--
            Several projects: a sideways run. On a laptop the section pins and the
            projects glide across as you scroll down (native scrolling, no hijack);
            on touch screens it is a swipe row; with reduced motion, the same row.
        --}}
        <div class="hs" data-hs data-scroll="pin" role="region" aria-label="{{ loc($data['label'] ?? null) }}">
            <div class="hs-sticky">
                <ul class="hs-track" data-hs-track>
                    @foreach ($projects as $project)
                        <li class="hs-card">
                            <x-plate
                                :href="Locale::route('work.show', ['slug' => $project->slugFor($locale)])"
                                :number="str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT)"
                                :title="loc($project->title)"
                                :kind="loc($project->kind)"
                                :year="$project->year"
                                :stack="collect($project->stack)->take(2)->implode(' · ')"
                                :summary="loc($project->summary)"
                                :asset="$project->cover"
                                :eager="$loop->first"
                                sizes="(min-width: 1024px) 52vw, 82vw"
                            />
                        </li>
                    @endforeach
                </ul>

                <p class="hs-meter t-label" aria-hidden="true">
                    <span><span data-hs-index>01</span> / {{ str_pad((string) $count, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="hs-bar"><span></span></span>
                </p>
            </div>
        </div>
    @else
        <div class="page">
            <x-project-grid :projects="$projects" />
        </div>
    @endif

    @if ($workIndex)
        <div class="page pb-20 pt-12 md:pb-36 md:pt-20">
            <p><x-button variant="secondary" :href="$workIndex">{{ __('site.cta.all_work') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
        </div>
    @endif
</section>
