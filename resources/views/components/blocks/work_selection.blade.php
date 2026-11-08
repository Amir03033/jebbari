@props(['data', 'page', 'number'])

@php
    use App\Content\Site;
    use App\Support\Locale;

    $mode = $data['mode'] ?? 'featured';
    $projects = Site::projects($mode === 'featured', $mode === 'selected' ? ($data['project_ids'] ?? []) : null)->take(max(1, min(4, (int) ($data['limit'] ?? 4))));
    $locale = app()->getLocale();
    $workIndex = Site::pageUrl('work');
@endphp

<section>
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" :meta="'('.$projects->count().')'" />
    <div class="page py-16 md:py-28">
        @if (loc($data['intro'] ?? null))
            <p class="t-body mb-12 max-w-[62ch] text-muted md:mb-20">{{ loc($data['intro']) }}</p>
        @endif

        <div class="grid-12 gap-y-16 md:gap-y-24">
            @foreach ($projects as $project)
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
                    :sizes="$loop->first ? '(min-width: 1360px) 1264px, 100vw' : '(min-width: 1360px) 620px, (min-width: 768px) 50vw, 100vw'"
                    :eager="false"
                />
            @endforeach
        </div>

        @if ($workIndex)
            <p class="mt-16 md:mt-24"><x-link :href="$workIndex" arrow class="t-h3">{{ __('site.cta.all_work') }}</x-link></p>
        @endif
    </div>
</section>
