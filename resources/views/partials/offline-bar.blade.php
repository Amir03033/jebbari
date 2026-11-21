{{-- Only for signed-in team members while the site is offline for everyone else. --}}
<div class="offline-bar" role="status">
    <p class="t-label"><span class="offline-bar-dot" aria-hidden="true"></span>{{ __('site.offline.banner') }}</p>
    <a href="{{ \App\Filament\Pages\Settings\SiteStatus::getUrl() }}" class="t-label link link-bare">{{ __('site.offline.banner_link') }}</a>
</div>
