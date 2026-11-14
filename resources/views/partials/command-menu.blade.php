@php
    use App\Content\Site;
    use App\Support\Locale;

    $items = Site::commandItems()->groupBy('group');
    $email = Site::email();
@endphp

{{--
    Command menu (Ctrl/Cmd + K, or "/"): jump to any page, project or article,
    or run a quick action. A native dialog, so focus is trapped and Escape
    closes it without any extra script; filtering happens in motion.js.
--}}
<dialog id="command-menu" class="cmd" aria-label="{{ __('site.command.title') }}" data-cmd data-found="{{ __('site.egg.found') }}">
    <div class="cmd-box">
        <div class="cmd-search">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4"/></svg>
            <input type="search" class="cmd-input" placeholder="{{ __('site.command.placeholder') }}" aria-label="{{ __('site.command.placeholder') }}" autocomplete="off" spellcheck="false" data-cmd-input>
            <form method="dialog"><button class="cmd-key t-label" aria-label="{{ __('site.nav.close') }}">Esc</button></form>
        </div>

        <div class="cmd-list" data-cmd-list>
            @foreach ($items as $group => $entries)
                <div class="cmd-group" data-cmd-group>
                    <p class="t-label cmd-group-title">{{ __('site.command.group_'.$group) }}</p>
                    @foreach ($entries as $entry)
                        <a href="{{ $entry['url'] }}" class="cmd-item" data-cmd-item>
                            <span>{{ $entry['label'] }}</span>
                            @if ($entry['hint'])<span class="t-label text-muted">{{ $entry['hint'] }}</span>@endif
                        </a>
                    @endforeach
                </div>
            @endforeach

            <div class="cmd-group" data-cmd-group>
                <p class="t-label cmd-group-title">{{ __('site.command.group_actions') }}</p>
                <button type="button" class="cmd-item" data-cmd-item data-theme-toggle><span>{{ __('site.theme.toggle') }}</span></button>
                <a href="{{ $switchUrl }}" class="cmd-item" data-cmd-item hreflang="{{ Locale::other() }}"><span>{{ __('site.command.language') }}</span><span class="t-label text-muted">{{ strtoupper(Locale::other()) }}</span></a>
                @if ($email)
                    <button type="button" class="cmd-item" data-cmd-item data-copy="{{ $email }}" data-copied="{{ __('site.contact.copied') }}"><span>{{ __('site.contact.copy') }}</span><span class="t-label text-muted" role="status" aria-live="polite" data-copy-status></span></button>
                @endif
                {{-- Not listed until you type its name: see "Easter eggs" in docs. --}}
                <button type="button" class="cmd-item" data-cmd-item data-cmd-secret="blueprint grid raster" data-egg="blueprint" hidden><span>{{ __('site.egg.blueprint') }}</span></button>
                <button type="button" class="cmd-item" data-cmd-item data-cmd-secret="confetti party feest" data-egg="confetti" hidden><span>{{ __('site.egg.confetti') }}</span></button>
            </div>

            <p class="cmd-empty text-muted" data-cmd-empty hidden>{{ __('site.command.empty') }}</p>
        </div>

        <p class="cmd-foot t-label text-muted" aria-hidden="true"><span>↑ ↓ {{ __('site.command.navigate') }}</span><span>↵ {{ __('site.command.open') }}</span></p>
    </div>
</dialog>
