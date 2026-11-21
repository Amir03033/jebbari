@if (\App\Support\SiteOffline::isOn())
    <a href="{{ \App\Filament\Pages\Settings\SiteStatus::getUrl() }}" class="me-3" title="De website is offline voor bezoekers">
        <x-filament::badge color="danger" icon="heroicon-m-power">Website offline</x-filament::badge>
    </a>
@endif
