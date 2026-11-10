@props(['projects', 'eagerFirst' => false])

@php
    use App\Support\Locale;

    $locale = app()->getLocale();
    // A composition, not a grid: wide, then a wide-and-tall pair, then wide again.
    $layout = [['col-span-12', 'wide'], ['col-span-12 md:col-span-7', 'standard'], ['col-span-12 md:col-span-5 md:mt-32', 'tall'], ['col-span-12', 'wide']];
@endphp

<div class="grid-12 gap-y-16 md:gap-y-24">
    @foreach ($projects as $project)
        @php([$span, $ratio] = $layout[$loop->index % count($layout)])
        <x-plate
            :class="$span"
            :tags="collect($project->stack ?? [])->map(fn ($t) => mb_strtolower(trim((string) $t)))->implode('|')"
            :ratio="$ratio"
            :large="$loop->first"
            :href="Locale::route('work.show', ['slug' => $project->slugFor($locale)])"
            :number="str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT)"
            :title="loc($project->title)"
            :kind="loc($project->kind)"
            :year="$project->year"
            :stack="collect($project->stack)->take($loop->first ? 3 : 2)->implode(' · ')"
            :summary="loc($project->summary)"
            :asset="$project->cover"
            :eager="$eagerFirst && $loop->first"
            :sizes="$loop->first ? '(min-width: 1360px) 1264px, 100vw' : '(min-width: 1360px) 700px, (min-width: 768px) 55vw, 100vw'"
        />
    @endforeach
</div>
