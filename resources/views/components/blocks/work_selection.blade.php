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
    <div class="page pb-10 pt-14 md:pb-16 md:pt-24">
        <div class="grid-12 items-end gap-y-6" data-reveal="group">
            <h2 class="t-display col-span-12 md:col-span-6"><x-lines :lines="[loc($data['label'] ?? null)]" /></h2>
            @if (loc($data['intro'] ?? null))
                <p class="t-statement rise col-span-12 max-w-[30ch] md:col-span-6 md:col-start-7" data-delay="4" data-fill>{{ loc($data['intro']) }}</p>
            @endif
        </div>
    </div>

    <div class="page">
        @if ($count > 1)
            {{--
                Several projects: a stack of case study cards. On a laptop each
                card settles under the header while the next one slides over it
                (native scrolling, nothing is taken over); on small screens and
                with reduced motion it is a plain list.
            --}}
            <ol class="stack" data-stack>
                @foreach ($projects as $project)
                    <li class="stack-item" data-scroll="stack">
                        <x-case-card
                            :href="Locale::route('work.show', ['slug' => $project->slugFor($locale)])"
                            :number="str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT)"
                            :title="loc($project->title)"
                            :kind="loc($project->kind)"
                            :year="$project->year"
                            :stack="collect($project->stack)->take(4)->all()"
                            :summary="loc($project->summary)"
                            :asset="$project->cover"
                            :eager="$loop->first"
                        />
                    </li>
                @endforeach
            </ol>
        @else
            <x-project-grid :projects="$projects" />
        @endif
    </div>

    @if ($workIndex)
        <div class="page pb-20 pt-10 md:pb-32 md:pt-16">
            <p data-reveal="up"><x-button variant="secondary" :href="$workIndex">{{ __('site.cta.all_work') }}<span class="arrow" aria-hidden="true">→</span></x-button></p>
        </div>
    @endif
</section>
