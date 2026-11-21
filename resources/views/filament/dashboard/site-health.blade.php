<x-filament-widgets::widget>
    <x-filament::section heading="De website" icon="heroicon-o-globe-alt">
        <p class="jb-eyebrow">Meest bekeken, 30 dagen</p>
        @if ($top === [])
            <p class="jb-muted">Nog geen bezoek gemeten.</p>
        @else
            @php
                $max = max(1, ...array_column($top, 'views'));
            @endphp
            <ol class="jb-bars">
                @foreach ($top as $row)
                    <li>
                        <span class="jb-bar-label" title="{{ $row['path'] }}">{{ $row['path'] }}</span>
                        <span class="jb-bar-value">{{ number_format($row['views'], 0, ',', '.') }}</span>
                        <span class="jb-bar"><span style="width: {{ round($row['views'] / $max * 100) }}%"></span></span>
                    </li>
                @endforeach
            </ol>
        @endif

        <p class="jb-eyebrow jb-gap">Live gemeten</p>
        @if ($uptime === [])
            <p class="jb-muted">Nog geen projecten met "Live meten". <a href="{{ $projectsUrl }}" class="jb-link">Aanzetten bij een project</a></p>
        @else
            <ul class="jb-list jb-compact">
                @foreach ($uptime as $u)
                    <li class="jb-row">
                        <span class="jb-pulse" data-state="{{ $u['summary'] === null ? 'wait' : (($u['summary']['online'] ?? true) === false ? 'down' : 'up') }}" aria-hidden="true"></span>
                        <span class="jb-row-text">
                            <span class="jb-row-title">{{ $u['host'] ?: $u['name'] }}</span>
                            <span class="jb-row-hint">
                                @if ($u['summary'] === null)
                                    Verzamelt metingen…
                                @else
                                    {{ number_format($u['summary']['uptime'], 2, ',', '') }}% uptime{{ $u['summary']['ms'] ? ' · '.$u['summary']['ms'].' ms' : '' }}
                                @endif
                            </span>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($numbers)
            <dl class="jb-numbers">
                @foreach ($numbers as $n)
                    <div>
                        <dt>{{ $n['label'] }}</dt>
                        <dd>
                            @if ($n['url'])
                                <a href="{{ $n['url'] }}" class="jb-link">{{ $n['value'] }}</a>
                            @else
                                {{ $n['value'] }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
