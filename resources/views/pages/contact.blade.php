@php
    use App\Content\BlockRenderer;
    use App\Content\Site;

    $email = Site::email();
    $booking = Site::setting('contact.booking_url');
    $hasIntro = BlockRenderer::blocks($page)->contains('type', 'intro');
    $start = $hasIntro ? 1 : 2;
    $next = str_pad((string) ($start + BlockRenderer::blocks($page, $start)->reject(fn ($b) => in_array($b['type'], ['meeting_line', 'reply_promise', 'image'], true))->count()), 2, '0', STR_PAD_LEFT);
    $sent = session('inquiry_sent');
    $budgets = collect(['' => __('site.contact.budget_choose'), 'lt-2.5k' => '< €2.5k', '2.5-7.5k' => '€2.5–7.5k', '7.5-15k' => '€7.5–15k', '15k+' => '€15k+', 'unsure' => __('site.contact.budget_unsure')]);
    $action = \App\Support\Locale::route('contact.send');
@endphp

<x-layouts.app :seo="$seo" :preview="$preview" :header-theme="$page->hasDarkOpening() ? 'dark' : 'light'">
    @if ($hasIntro)
        <x-blocks :page="$page" :only="['intro']" />
    @else
        <x-page-header :page="$page" />
    @endif

    <section>
        <div class="page py-16 md:py-28">
            <div class="grid-12 gap-y-16">
                <div class="col-span-12 md:col-span-6" data-reveal="group">
                    @if ($email)
                        <p class="t-label rise mb-3 text-muted">{{ __('site.footer.email') }}</p>
                        <p class="rise" data-delay="1">
                            <x-link href="mailto:{{ $email }}" class="text-[clamp(1.5rem,3vw,3rem)] font-medium tracking-[-0.035em] break-all">{{ $email }}</x-link>
                        </p>
                        <p class="rise mt-4" data-delay="2">
                            <button type="button" class="t-label link link-bare" data-copy="{{ $email }}" data-copied="{{ __('site.contact.copied') }}">{{ __('site.contact.copy') }}</button>
                            <span class="t-label ml-3 text-muted" role="status" aria-live="polite" data-copy-status></span>
                        </p>
                    @endif
                    @if ($booking)
                        <p class="rise mt-8" data-delay="3"><x-link :href="$booking" arrow rel="noopener" target="_blank">{{ __('site.cta.book') }}</x-link></p>
                    @endif
                </div>

                <div id="contact-form" class="card col-span-12 scroll-mt-28 p-6 focus:outline-none md:col-span-6 md:p-10" tabindex="-1">
                    @if ($sent)
                        <div role="status">
                            <h2 class="t-h3">{{ __('site.contact.sent_title') }}</h2>
                            <p class="t-body mt-3 text-muted">{{ __('site.contact.sent_text') }}</p>
                        </div>
                    @else
                        <form method="post" action="{{ $action }}#contact-form" class="grid gap-6" novalidate>
                            @csrf
                            <input type="hidden" name="_t" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
                            {{-- Honeypot: invisible to people and assistive technology, tempting to bots. --}}
                            <div class="sr-only" aria-hidden="true">
                                <label for="f-website">{{ __('site.contact.leave_empty') }}</label>
                                <input type="text" id="f-website" name="website" tabindex="-1" autocomplete="off" value="">
                            </div>

                            @if ($errors->any())
                                <p role="alert" class="rounded-ui border border-fg p-4 text-sm font-medium">{{ __('site.contact.fix_errors') }}</p>
                            @endif

                            <x-field name="name" :label="__('site.contact.name')" required autocomplete="name" maxlength="120" :value="old('name')" :error="$errors->first('name')" />
                            <x-field name="email" type="email" :label="__('site.contact.email')" required autocomplete="email" maxlength="190" :value="old('email')" :error="$errors->first('email')" />
                            <x-field name="company" :label="__('site.contact.company')" autocomplete="organization" maxlength="160" :value="old('company')" :error="$errors->first('company')" />
                            <x-field name="budget" type="select" :label="__('site.contact.budget')" :options="$budgets" :value="old('budget')" :error="$errors->first('budget')" />
                            <x-field name="message" type="textarea" :label="__('site.contact.message')" required maxlength="5000" :value="old('message')" :error="$errors->first('message')" />

                            <div><x-button type="submit">{{ __('site.contact.send') }}<span class="arrow" aria-hidden="true">→</span></x-button></div>
                            <p class="text-sm text-muted">{{ __('site.contact.privacy_note') }}
                                @if ($privacy = Site::pageUrl('privacy'))<x-link :href="$privacy">{{ __('site.footer.privacy') }}</x-link>@endif
                            </p>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <x-blocks :page="$page" :start="$start" :except="['intro']" />
</x-layouts.app>
