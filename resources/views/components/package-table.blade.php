@props(['rows', 'showService' => false])

{{-- Packages with "from" prices in tabular figures. A package without a price says so; no price is ever guessed. --}}
<div>
    @foreach ($rows as $row)
        <div class="relative" data-reveal="group" data-delay="{{ min($loop->index, 3) }}">
            <span class="rule" aria-hidden="true"></span>
            <div class="grid-12 rise items-baseline gap-y-3 py-6 md:py-8">
                <div class="col-span-12 md:col-span-4">
                    @if ($showService)
                        <p class="t-label text-muted"><a href="{{ $row['url'] }}" class="link link-bare">{{ $row['service'] }}</a></p>
                    @endif
                    <h3 class="t-h3 mt-1">{{ $row['name'] }}</h3>
                </div>
                <ul class="col-span-12 text-muted md:col-span-5">
                    @foreach ($row['scope'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
                <p class="col-span-12 tabular-nums md:col-span-3 md:text-right">
                    @if ($row['price'] !== null)
                        <span class="t-label block text-muted">{{ __('site.packages.from') }}</span>
                        <span class="t-h3">{{ \App\Content\PackagePricing::format($row['price']) }}</span>
                    @else
                        <span class="t-label text-muted">{{ __('site.packages.on_request') }}</span>
                    @endif
                    @if ($row['note'])
                        <span class="mt-1 block text-sm text-muted">{{ $row['note'] }}</span>
                    @endif
                </p>
            </div>
        </div>
    @endforeach
</div>
