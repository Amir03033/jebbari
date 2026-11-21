{{ __('scan.mail.intro', ['domain' => $scan->domain], $locale) }}

{{ __('scan.mail.scores', [], $locale) }}
@if ($overall !== null)
- {{ __('scan.report.overall', [], $locale) }}: {{ $overall }}
@endif
@foreach (\App\Models\Scan::CATEGORIES as $category)
- {{ __('scan.category.'.$category, [], $locale) }}: {{ $scores[$category] ?? '–' }}
@endforeach

{{ __('scan.mail.link', [], $locale) }}
{{ $reportUrl }}

{{ __('scan.mail.cta', [], $locale) }}
{{ $contactUrl }}

--
{{ __('scan.mail.footer', ['days' => config('scan.retention_days')], $locale) }}
