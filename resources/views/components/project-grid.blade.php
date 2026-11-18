@props(['projects', 'eagerFirst' => false])

@php
    use App\Support\Locale;

    $locale = app()->getLocale();
    // An even rhythm: every image in the same 16:10 frame, two to a row. With an
    // odd number of projects the first one leads across the full width, its
    // caption beside the image, so no row is left half empty.
    $lead = $projects->count() % 2 === 1;
@endphp

<div class="grid-12 gap-y-16 md:gap-x-10 md:gap-y-24">
    @foreach ($projects as $project)
        @php($isLead = $lead && $loop->first)
        <x-plate
            :class="$isLead ? 'col-span-12' : 'col-span-12 md:col-span-6'"
            :tags="collect($project->stack ?? [])->map(fn ($t) => mb_strtolower(trim((string) $t)))->implode('|')"
            :lead="$isLead"
            :large="$isLead"
            :href="Locale::route('work.show', ['slug' => $project->slugFor($locale)])"
            :number="str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT)"
            :title="loc($project->title)"
            :kind="loc($project->kind)"
            :year="$project->year"
            :stack="collect($project->stack)->take($isLead ? 3 : 2)->implode(' · ')"
            :summary="loc($project->summary)"
            :asset="$project->cover"
            :eager="$eagerFirst && $loop->first"
            :sizes="$isLead ? '(min-width: 1360px) 840px, (min-width: 768px) 62vw, 100vw' : '(min-width: 1360px) 660px, (min-width: 768px) 50vw, 100vw'"
        />
    @endforeach
</div>
