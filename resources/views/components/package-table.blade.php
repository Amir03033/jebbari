@props(['rows', 'showService' => false])

{{-- Packages as rounded cards. A package without a price says so; no price is ever guessed. --}}
<div class="grid-12 gap-y-6">
    @foreach ($rows as $row)
        <div @class(['card spot col-span-12 flex flex-col justify-between gap-10 p-7 md:p-10', 'md:col-span-6' => $rows->count() > 1, 'lg:col-span-4' => $rows->count() > 2]) data-reveal="up" data-delay="{{ min($loop->index, 3) }}">
            <div>
                @if ($showService)
                    <p class="t-label text-muted"><a href="{{ $row['url'] }}" class="link link-bare">{{ $row['service'] }}</a></p>
                @endif
                <h3 class="t-h3 mt-2">{{ $row['name'] }}</h3>
                <ul class="mt-6 grid gap-2 text-muted">
                    @foreach ($row['scope'] as $line)
                        <li class="flex gap-3"><span class="mt-[0.7em] size-1 shrink-0 rounded-full bg-current" aria-hidden="true"></span>{{ $line }}</li>
                    @endforeach
                </ul>
            </div>
            <p class="tabular-nums">
                @if ($row['price'] !== null)
                    <span class="t-label block text-muted">{{ __('site.packages.from') }}</span>
                    <span class="text-4xl font-medium tracking-[-0.03em]">{{ \App\Content\PackagePricing::format($row['price']) }}</span>
                @else
                    <span class="t-label text-muted">{{ __('site.packages.on_request') }}</span>
                @endif
                @if ($row['note'])
                    <span class="mt-1 block text-sm text-muted">{{ $row['note'] }}</span>
                @endif
            </p>
        </div>
    @endforeach
</div>
