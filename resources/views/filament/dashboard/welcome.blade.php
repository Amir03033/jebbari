<x-filament-widgets::widget>
    <div class="jb-welcome">
        <div>
            <p class="jb-eyebrow">{{ ucfirst($date) }}</p>
            <h2 class="jb-greeting">{{ $greeting }}@if ($name), {{ $name }}@endif<span class="jb-dot" aria-hidden="true"></span></h2>
            <p class="jb-status" data-offline="{{ $offline ? 'true' : 'false' }}">
                <span class="jb-status-dot" aria-hidden="true"></span>
                @if ($offline)
                    De website is <strong>offline</strong> voor bezoekers.
                @else
                    De website is online.
                @endif
                @if ($statusUrl)
                    <a href="{{ $statusUrl }}" class="jb-link">{{ $offline ? 'Zet weer online' : 'Beheren' }}</a>
                @endif
            </p>
        </div>
        <div class="jb-actions">
            @foreach ($actions as $action)
                <x-filament::button tag="a" :href="$action['url']" :icon="$action['icon']" color="gray" size="sm">{{ $action['label'] }}</x-filament::button>
            @endforeach
            <x-filament::button tag="a" :href="$siteUrl" target="_blank" icon="heroicon-m-arrow-top-right-on-square" size="sm">Bekijk website</x-filament::button>
        </div>
    </div>
</x-filament-widgets::widget>
