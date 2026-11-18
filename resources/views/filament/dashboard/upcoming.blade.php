<x-filament-widgets::widget>
    <x-filament::section heading="Komende afspraken" icon="heroicon-o-calendar-days">
        <x-slot name="afterHeader">
            <x-filament::link :href="$indexUrl" size="sm">Alle</x-filament::link>
        </x-slot>
        @if ($appointments === [])
            <div class="jb-empty">
                <x-filament::icon icon="heroicon-o-calendar" class="jb-empty-icon" />
                <p>Geen afspraken gepland.@if ($hoursUrl) <a href="{{ $hoursUrl }}" class="jb-link">Beschikbaarheid instellen</a>@endif</p>
            </div>
        @else
            <ul class="jb-list">
                @foreach ($appointments as $a)
                    <li class="jb-row">
                        <span class="jb-date"><span>{{ $a['month'] }}</span><strong>{{ $a['date'] }}</strong></span>
                        <span class="jb-row-text">
                            <span class="jb-row-title">{{ $a['name'] }}</span>
                            <span class="jb-row-hint">{{ ucfirst($a['day']) }} {{ $a['time'] }}@if ($a['type']) · {{ $a['type'] }}@endif</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
