<x-filament-widgets::widget>
    <x-filament::section heading="Wat aandacht nodig heeft" icon="heroicon-o-check-circle">
        @if ($items === [])
            <div class="jb-empty">
                <x-filament::icon icon="heroicon-o-sparkles" class="jb-empty-icon" />
                <p><strong>Alles is bij.</strong> Geen ongelezen berichten, nieuwe leads of openstaande wijzigingen.</p>
            </div>
        @else
            <ul class="jb-list">
                @foreach ($items as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="jb-row">
                            <span class="jb-icon" data-tone="{{ $item['tone'] }}"><x-filament::icon :icon="$item['icon']" /></span>
                            <span class="jb-row-text">
                                <span class="jb-row-title">{{ $item['label'] }}</span>
                                <span class="jb-row-hint">{{ $item['hint'] }}</span>
                            </span>
                            <x-filament::badge :color="$item['tone']">{{ $item['count'] }}</x-filament::badge>
                            <x-filament::icon icon="heroicon-m-chevron-right" class="jb-chevron" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
