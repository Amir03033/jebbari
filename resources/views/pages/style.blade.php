@php
    $swatch = ['paper' => 'bg-paper', 'paper-2' => 'bg-paper-2', 'ink' => 'bg-ink', 'ink-2' => 'bg-ink-2', 'signal' => 'bg-signal'];
    $budget = ['' => 'Choose a range', 'lt-2.5' => '< €2.5k', '2.5-7.5' => '€2.5–7.5k', '7.5-15' => '€7.5–15k', '15+' => '€15k+', 'unsure' => 'Not sure'];
@endphp

<x-layouts.app title="Style" description="Design token test page." :noindex="true">
    {{-- Intro --}}
    <section class="page pb-16 pt-16 md:pt-24">
        <p class="t-label mb-6 text-muted">Internal / Phase 1 / Not part of the public site</p>
        <h1 class="t-display max-w-[9ch]">Style<span class="text-signal">.</span></h1>
        <p class="t-body mt-10 text-muted">
            Tokens, type, buttons, fields and hairlines, exactly as specified in the brief. Nothing here is a page design.
        </p>
    </section>

    {{-- 01 Colour --}}
    <section>
        <x-section-label number="01" label="Colour" meta="(5 tokens + 2 hairlines)" />
        <div class="page py-16 md:py-24">
            <ul class="grid-12 gap-y-10">
                @foreach ($tokens as $name => [$hex, $use])
                    <li class="col-span-6 md:col-span-4 lg:col-span-2">
                        <div class="aspect-square border border-line {{ $swatch[$name] }}"></div>
                        <p class="mt-3 font-medium">{{ $name }}</p>
                        <p class="t-label mt-1 text-muted">{{ $hex }}</p>
                        <p class="mt-2 text-sm text-muted">{{ $use }}</p>
                    </li>
                @endforeach
                <li class="col-span-6 md:col-span-4 lg:col-span-2">
                    <div class="flex aspect-square flex-col justify-center gap-6 border border-line p-4">
                        <div class="border-t border-line"></div>
                        <div class="on-ink -m-4 p-4"><div class="border-t border-line"></div></div>
                    </div>
                    <p class="mt-3 font-medium">line / line-dark</p>
                    <p class="t-label mt-1 text-muted">14% / 16% alpha</p>
                    <p class="mt-2 text-sm text-muted">1px hairlines on paper and on ink</p>
                </li>
            </ul>

            <div class="mt-20">
                <p class="t-label mb-4 text-muted">Signal budget</p>
                <p class="t-body text-muted">
                    Orange appears only as the wordmark dot, the status dot, the selection background, focus rings, the active nav marker and the link underline on hover. Select this sentence, tab to a control, or hover a link below to see it.
                </p>
            </div>

            <div class="mt-20">
                <p class="t-label mb-4 text-muted">Measured contrast (WCAG)</p>
                <dl>
                    @foreach ($contrast as [$pair, $ratio])
                        <x-spec-row :label="$pair" :value="number_format($ratio, 2).' : 1'" :note="$ratio >= 4.5 ? 'AA text' : ($ratio >= 3 ? 'AA non-text only' : 'fails')" />
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    {{-- 02 Type --}}
    <section>
        <x-section-label number="02" label="Type" meta="(Satoshi + Geist Mono)" />
        <div class="page py-16 md:py-24">
            <div class="grid-12 gap-y-16">
                <div class="col-span-12">
                    <p class="t-label mb-4 text-muted">Display / clamp(3.5rem, 9vw, 9rem) / 0.95 / −0.04em / 500</p>
                    <p class="t-display">Software, done properly<span class="text-signal">.</span></p>
                </div>
                <div class="col-span-12">
                    <p class="t-label mb-4 text-muted">H2 / clamp(2rem, 4.5vw, 4rem) / 1.02 / −0.03em / 500</p>
                    <p class="t-h2 max-w-[18ch]">Built to a standard, measured on this very site</p>
                </div>
                <div class="col-span-12 md:col-span-6">
                    <p class="t-label mb-4 text-muted">H3 / 1.5rem / 1.2 / −0.015em / 500</p>
                    <p class="t-h3">Web applications, websites, care and hosting</p>
                </div>
                <div class="col-span-12 md:col-span-6">
                    <p class="t-label mb-4 text-muted">Body / 1.125rem / 1.6 / max 62ch</p>
                    <p class="t-body">We design and build web applications and websites for businesses that care how their software feels, performs and lasts. Supporting text uses the secondary ink and never runs wider than sixty-two characters.</p>
                    <p class="t-body mt-4 text-muted">Secondary text, <code class="t-label">--ink-2</code> on paper.</p>
                </div>
                <div class="col-span-12 md:col-span-6">
                    <p class="t-label mb-4 text-muted">Mono label / 0.75rem / 1.4 / +0.06em / uppercase</p>
                    <p class="t-label">01 &nbsp; JF Cuts &nbsp; Booking web app &nbsp; 2026 &nbsp; Laravel · Alpine</p>
                </div>
                <div class="col-span-12 md:col-span-6">
                    <p class="t-label mb-4 text-muted">Tabular figures</p>
                    <p class="text-3xl font-medium tabular-nums">0123456789<br>1111111111</p>
                </div>
            </div>

            <div class="mt-20">
                <p class="t-label mb-4 text-muted">Font status</p>
                <dl>
                    <x-spec-row label="Satoshi (display, body)" :value="config('site.fonts.satoshi') ? 'Self-hosted' : 'Not loaded'" :note="config('site.fonts.satoshi') ? '' : 'Download blocked'" />
                    <x-spec-row label="Fallback in use" value="system-ui" />
                    <x-spec-row label="Geist Mono" value="Self-hosted, Latin" note="400 + 500" />
                </dl>
            </div>
        </div>
    </section>

    {{-- 03 Buttons and links --}}
    <section>
        <x-section-label number="03" label="Buttons and links" meta="(2px radius, no accent fill)" />
        <div class="page py-16 md:py-24">
            <div class="grid-12 gap-y-12">
                <div class="col-span-12 md:col-span-6">
                    <p class="t-label mb-6 text-muted">Buttons</p>
                    <div class="flex flex-wrap gap-4">
                        <x-button>Send enquiry</x-button>
                        <x-button variant="secondary">Book a call</x-button>
                        <x-button disabled>Disabled</x-button>
                    </div>
                </div>
                <div class="col-span-12 md:col-span-6">
                    <p class="t-label mb-6 text-muted">Links</p>
                    <div class="flex flex-col items-start gap-5">
                        <x-link href="#" arrow class="t-h3">Start a project</x-link>
                        <p class="t-body">An inline link sits <x-link href="#">inside a sentence</x-link> with a hairline that the accent draws over on hover.</p>
                        <x-link href="#" bare class="t-label">Bare nav-style link</x-link>
                    </div>
                </div>
                <div class="col-span-12">
                    <p class="t-label mb-6 text-muted">Focus (tab through the controls above)</p>
                    <div class="flex flex-wrap gap-6">
                        <button type="button" class="btn btn-secondary" id="focus-demo">Focused example</button>
                        <p class="t-body text-muted">Focus ring: 2px signal, 2px offset. <x-link href="#">Skip link</x-link> appears on first Tab press.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 04 Form fields --}}
    <section>
        <x-section-label number="04" label="Form fields" meta="(labels above, errors by text)" />
        <div class="page py-16 md:py-24">
            <form class="grid-12 gap-y-8" onsubmit="return false" novalidate>
                <x-field class="col-span-12 md:col-span-6" name="name" label="Name" required />
                <x-field class="col-span-12 md:col-span-6" name="email" type="email" label="Email" required hint="We only use this to reply." />
                <x-field class="col-span-12 md:col-span-6" name="company" label="Company" />
                <x-field class="col-span-12 md:col-span-6" name="budget" type="select" label="Budget" :options="$budget" />
                <x-field class="col-span-12 md:col-span-8" name="brief" type="textarea" label="What do you want to build?" required />
                <x-field class="col-span-12 md:col-span-6" name="invalid" label="Error state" error="Enter a valid email address." required />
                <div class="col-span-12"><x-button type="submit">Send enquiry</x-button></div>
            </form>
        </div>
    </section>

    {{-- 05 Hairlines --}}
    <section>
        <x-section-label number="05" label="Hairlines and index" meta="(structure without boxes)" />
        <div class="page py-16 md:py-24">
            <p class="t-label mb-4 text-muted">Spec rows, tabular numbers (values show as not yet measured)</p>
            <dl>
                <x-spec-row label="Lighthouse performance (mobile)" value="Not yet measured" note="Phase 4" />
                <x-spec-row label="Largest contentful paint" value="Not yet measured" note="Phase 4" />
                <x-spec-row label="Third-party requests" value="0" note="By design" />
            </dl>
        </div>
    </section>

    {{-- 06 Grid, space, shape --}}
    <section>
        <x-section-label number="06" label="Grid, space and shape" meta="(12 columns)" />
        <div class="page py-16 md:py-24">
            <p class="t-label mb-4 text-muted">12 columns / 24px gutters (16px mobile)</p>
            <div class="grid-12 h-24" aria-hidden="true">
                @for ($i = 1; $i <= 12; $i++)
                    <div class="border-x border-line"></div>
                @endfor
            </div>

            <div class="grid-12 mt-16 gap-y-10">
                <div class="col-span-12 md:col-span-9">
                    <p class="t-label mb-4 text-muted">Headline columns 1–9, support columns 8–12</p>
                    <p class="t-h2">Asymmetric, left-aligned, with whitespace as a feature.</p>
                </div>
                <div class="col-span-12 md:col-span-5 md:col-start-8">
                    <p class="t-body text-muted">Supporting text sits to the right and never centres.</p>
                </div>
            </div>

            <div class="mt-16 flex flex-wrap items-end gap-12">
                <div>
                    <div class="size-24 border border-line bg-paper-2 rounded-ui"></div>
                    <p class="t-label mt-3 text-muted">UI radius 2px</p>
                </div>
                <div>
                    <div class="size-24 border border-line bg-paper-2"></div>
                    <p class="t-label mt-3 text-muted">Images, plates 0</p>
                </div>
                <div>
                    <div class="h-24 w-40 border border-line bg-paper-2 shadow-[0_30px_80px_-40px_rgba(14,14,15,0.25)]"></div>
                    <p class="t-label mt-3 text-muted">Plate shadow (only one)</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 07 Motion --}}
    <section>
        <x-section-label number="07" label="Motion" meta="(Phase 1.5 demonstration)" />
        <div class="page py-16 md:py-24">
            <div class="grid-12 gap-y-6">
                <p class="t-body col-span-12 text-muted md:col-span-7">
                    Every block below animates once, when it enters view, using only CSS and one IntersectionObserver. Use Replay to watch a block again. Nothing here loops, bounces or moves layout.
                </p>
            </div>
            <p class="t-label mt-4 text-muted">Currently: <span data-motion-status>…</span></p>

            {{-- A. Hero --}}
            <div class="mt-24">
                <div class="mb-8 flex items-center justify-between gap-6">
                    <p class="t-label text-muted">A / Hero entrance (about 1.6s, full stop lands last)</p>
                    <x-button variant="secondary" data-replay="#demo-hero">Replay</x-button>
                </div>
                <div id="demo-hero" data-reveal="group" data-hero>
                    <p class="t-label rise text-muted" data-delay="1">Software studio, Zwolle &amp; Amsterdam</p>
                    <h2 class="t-display mt-6 md:max-w-[75%]">
                        <x-lines :lines="['Software, done', 'properly']" stop />
                    </h2>
                    <div class="grid-12 mt-10">
                        <p class="t-body rise col-span-12 text-muted md:col-span-5 md:col-start-8" data-delay="12">
                            We design and build web applications and websites for businesses that care how their software feels, performs and lasts.
                        </p>
                        <div class="rise col-span-12 mt-6 md:col-span-5 md:col-start-8" data-delay="14">
                            <x-link href="#" arrow class="t-h3">Start a project</x-link>
                        </div>
                    </div>
                </div>
            </div>

            {{-- B. Type --}}
            <div class="mt-32">
                <div class="mb-8 flex items-center justify-between gap-6">
                    <p class="t-label text-muted">B / Staggered type: masked lines, then paragraphs</p>
                    <x-button variant="secondary" data-replay="#demo-type">Replay</x-button>
                </div>
                <div id="demo-type" data-reveal="group" class="grid-12 gap-y-8">
                    <h2 class="t-h2 col-span-12 md:col-span-7">
                        <x-lines :lines="['Built to a standard,', 'measured on this site']" />
                    </h2>
                    <div class="col-span-12 md:col-span-5">
                        <p class="t-body rise text-muted" data-delay="4">Each paragraph rises 16px over 700ms, one 60ms step after the last.</p>
                        <p class="t-body rise mt-4 text-muted" data-delay="5">Groups of four at most, so long content never feels slow.</p>
                    </div>
                </div>
            </div>

            {{-- C. Rows --}}
            <div class="mt-32">
                <div class="mb-8 flex items-center justify-between gap-6">
                    <p class="t-label text-muted">C / Rows: hairline draws, then cells rise. Numbers never count up.</p>
                    <x-button variant="secondary" data-replay="#demo-rows">Replay</x-button>
                </div>
                <dl id="demo-rows">
                    <x-spec-row data-delay="1" label="Third-party requests" value="0" note="By design" />
                    <x-spec-row data-delay="3" label="Lighthouse performance (mobile)" value="Not yet measured" note="Phase 4" />
                    <x-spec-row data-delay="5" label="Largest contentful paint" value="Not yet measured" note="Phase 4" />
                </dl>
            </div>

            {{-- D. Plates --}}
            <div class="mt-32">
                <div class="mb-8 flex items-center justify-between gap-6">
                    <p class="t-label text-muted">D / Plates: image wipe, hover scale 1.01, "View case" label, caption hairline</p>
                    <x-button variant="secondary" data-replay="#demo-plates">Replay</x-button>
                </div>
                <div id="demo-plates" class="grid-12 gap-y-12">
                    <x-plate class="col-span-12 md:col-span-6" href="#" number="01" title="Project one" kind="Web application" year="2026" stack="Laravel · Alpine" placeholder="[[ OWNER: screenshot 1 ]]" />
                    <x-plate class="col-span-12 md:col-span-6" data-delay="3" href="#" number="02" title="Project two" kind="Dashboard" year="2026" stack="Laravel · Vite" placeholder="[[ OWNER: screenshot 2 ]]" />
                </div>
                <p class="t-label mt-6 text-muted">Placeholders only: no screenshots exist yet. Hover or Tab to a plate to see the interaction.</p>
            </div>

            {{-- E. Process --}}
            <div class="mt-32">
                <p class="t-label mb-8 text-muted">E / Process timeline: scroll through. The line fills; steps light as they pass.</p>
                <div class="grid-12 gap-y-10">
                    <div class="col-span-12 md:col-span-5">
                        <div class="md:sticky md:top-28">
                            <h3 class="t-h2">Process</h3>
                            <p class="t-label mt-4 text-muted">Sticky title, steps scroll</p>
                        </div>
                    </div>
                    <div class="relative col-span-12 pl-8 md:col-span-6 md:col-start-7 md:pl-12" data-process>
                        <span class="process-track" aria-hidden="true"><span class="process-fill"></span></span>
                        @foreach ([['01', 'Talk', 'We listen, ask the awkward questions, and agree scope and price in writing.'], ['02', 'Design', 'Wireframes and a clickable design you approve before any code.'], ['03', 'Build', 'Weekly working builds you can open and use. No surprises.'], ['04', 'Launch', 'We ship, monitor, and stay on call.']] as [$n, $name, $text])
                            <div class="process-step py-14 md:py-20" data-step>
                                <p class="t-label">{{ $n }}</p>
                                <h3 class="t-h3 mt-2">{{ $name }}</h3>
                                <p class="t-body mt-3">{{ $text }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- F. Menu and transitions --}}
            <div class="mt-32 grid-12 gap-y-6">
                <div class="col-span-12 md:col-span-6">
                    <p class="t-label mb-3 text-muted">F / Mobile menu</p>
                    <p class="t-body text-muted">Resize below 768px and open the menu: the panel settles, links rise in a stagger and their hairlines draw. Closing is faster and has no stagger.</p>
                </div>
                <div class="col-span-12 md:col-span-6">
                    <p class="t-label mb-3 text-muted">G / Page transitions</p>
                    <p class="t-body text-muted">
                        <x-link href="{{ route('en.work') }}">Open a page</x-link> to see a 250ms cross-fade in browsers that support it. Elsewhere it simply navigates.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- 08 Dark section --}}
    <section class="on-ink">
        <x-section-label number="08" label="On ink" meta="(dark section, 1 of 2)" />
        <div class="page py-16 md:py-24">
            <h2 class="t-h2 max-w-[16ch]">Built to a standard</h2>
            <p class="t-body mt-6 text-muted">Paper text on ink. Muted text, hairlines and buttons follow automatically.</p>
            <div class="mt-10 flex flex-wrap items-center gap-6">
                <x-button>Primary on ink</x-button>
                <x-button variant="secondary">Secondary on ink</x-button>
                <x-link href="#">Text link</x-link>
            </div>
            <dl class="mt-16">
                <x-spec-row label="Third-party requests" value="0" note="By design" />
                <x-spec-row label="Hosting region" value="Not yet decided" note="Owner" />
            </dl>
        </div>
    </section>
</x-layouts.app>
