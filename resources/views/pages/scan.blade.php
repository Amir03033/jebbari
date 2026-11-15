@php
    use App\Content\Site;
    use App\Models\Scan;

    $days = config('scan.retention_days');
@endphp

{{-- The free website check: the form, what is checked and what it is not. --}}
<x-layouts.app :seo="$seo">
    <x-page-opening number="01" :label="__('scan.page.label')" :title="__('scan.page.heading')" :lead="__('scan.page.lead')" />

    <section>
        <div class="page pb-16 md:pb-28">
            <div class="grid-12 gap-y-16">
                <div id="scan-form" class="card col-span-12 scroll-mt-28 p-6 focus:outline-none md:col-span-6 md:p-10" tabindex="-1">
                    <form method="post" action="{{ \App\Support\Locale::route('scan.store') }}" class="grid gap-6" novalidate>
                        @csrf
                        <input type="hidden" name="_t" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
                        {{-- Honeypot: invisible to people and assistive technology, tempting to bots. --}}
                        <div class="sr-only" aria-hidden="true">
                            <label for="f-website">{{ __('site.contact.leave_empty') }}</label>
                            <input type="text" id="f-website" name="website" tabindex="-1" autocomplete="off" value="">
                        </div>

                        @if ($errors->any())
                            <p role="alert" class="rounded-ui border border-fg p-4 text-sm font-medium">{{ __('scan.form.fix_errors') }}</p>
                        @endif

                        <x-field name="url" type="url" :label="__('scan.form.url')" :hint="__('scan.form.url_hint')" required autocomplete="url" maxlength="2048" :value="old('url')" :error="$errors->first('url')" />
                        <x-field name="email" type="email" :label="__('scan.form.email')" :hint="__('scan.form.email_hint')" required autocomplete="email" maxlength="190" :value="old('email')" :error="$errors->first('email')" />

                        <x-scan.checkbox name="privacy" :label="__('scan.form.privacy', ['days' => $days])" required :error="$errors->first('privacy')" :checked="(bool) old('privacy')" />
                        <x-scan.checkbox name="contact_consent" :label="__('scan.form.contact')" :checked="(bool) old('contact_consent')" />

                        @if ($turnstile)
                            <div class="cf-turnstile" data-sitekey="{{ $turnstile }}" data-language="{{ app()->getLocale() }}"></div>
                            @push('head')
                                <script src="{{ \App\Scan\Turnstile::HOST }}/turnstile/v0/api.js" async defer></script>
                            @endpush
                        @endif

                        <div><x-button type="submit">{{ __('scan.form.submit') }}<span class="arrow" aria-hidden="true">→</span></x-button></div>

                        @if ($privacy = Site::pageUrl('privacy'))
                            <p class="text-sm text-muted"><x-link :href="$privacy">{{ __('scan.form.privacy_link') }}</x-link></p>
                        @endif
                    </form>
                </div>

                <div class="col-span-12 md:col-span-5 md:col-start-8">
                    <div data-reveal="group">
                        <p class="t-label rise text-muted">{{ __('scan.page.what_label') }}</p>
                        <dl class="mt-4">
                            @foreach (Scan::CATEGORIES as $category)
                                <div class="scan-what">
                                    <span class="rule" aria-hidden="true"></span>
                                    <dt class="t-h3 rise">{{ __('scan.category.'.$category) }}</dt>
                                    <dd class="rise mt-2 text-muted" data-delay="1">{{ __('scan.category_text.'.$category) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <div class="mt-12" data-reveal="group">
                        <p class="t-label rise text-muted">{{ __('scan.page.limits_label') }}</p>
                        <ul class="scan-limits mt-4">
                            @foreach (__('scan.page.limits') as $limit)
                                <li class="rise text-muted" data-delay="{{ $loop->index + 1 }}">{{ $limit }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
