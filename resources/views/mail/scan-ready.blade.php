@php
    // E-mail clients do not read CSS variables: the light theme tokens from resources/css/app.css, written out.
    $paper = '#fafaf8';
    $card = '#f1f1ee';
    $ink = '#0b0c0e';
    $muted = '#5d5e64';
    $line = '#e2e2df';
    $font = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif";
    $mono = "ui-monospace, 'SF Mono', Menlo, Consolas, monospace";
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('scan.mail.subject', ['domain' => $scan->domain], $locale) }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $paper }};color:{{ $ink }};font-family:{{ $font }};">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $paper }};">
    <tr>
        <td align="center" style="padding:40px 16px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
                <tr>
                    <td style="padding-bottom:32px;font-size:20px;font-weight:600;letter-spacing:-0.03em;">jebbari<span style="color:#2b4ef5;">.</span></td>
                </tr>
                <tr>
                    <td style="font-family:{{ $mono }};font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:{{ $muted }};padding-bottom:12px;">{{ __('scan.name', [], $locale) }} · {{ $scan->domain }}</td>
                </tr>
                <tr>
                    <td style="font-size:28px;line-height:1.15;font-weight:500;letter-spacing:-0.03em;padding-bottom:24px;">{{ __('scan.mail.intro', ['domain' => $scan->domain], $locale) }}</td>
                </tr>
                <tr>
                    <td style="padding-bottom:28px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $line }};">
                            @if ($overall !== null)
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid {{ $line }};font-size:15px;font-weight:500;">{{ __('scan.report.overall', [], $locale) }}</td>
                                    <td align="right" style="padding:12px 0;border-bottom:1px solid {{ $line }};font-size:15px;font-weight:500;font-variant-numeric:tabular-nums;">{{ $overall }}</td>
                                </tr>
                            @endif
                            @foreach (\App\Models\Scan::CATEGORIES as $category)
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid {{ $line }};font-size:15px;color:{{ $muted }};">{{ __('scan.category.'.$category, [], $locale) }}</td>
                                    <td align="right" style="padding:12px 0;border-bottom:1px solid {{ $line }};font-size:15px;font-variant-numeric:tabular-nums;">{{ $scores[$category] ?? '–' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:15px;line-height:1.6;color:{{ $muted }};padding-bottom:16px;">{{ __('scan.mail.link', [], $locale) }}</td>
                </tr>
                <tr>
                    <td style="padding-bottom:36px;">
                        <a href="{{ $reportUrl }}" style="display:inline-block;background:{{ $ink }};color:{{ $paper }};text-decoration:none;font-size:15px;font-weight:500;padding:13px 22px;border-radius:999px;">{{ __('scan.mail.button', [], $locale) }} &rarr;</a>
                    </td>
                </tr>
                <tr>
                    <td style="background:{{ $card }};border:1px solid {{ $line }};border-radius:18px;padding:24px;">
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">{{ __('scan.mail.cta', [], $locale) }}</p>
                        <a href="{{ $contactUrl }}" style="color:{{ $ink }};font-size:15px;font-weight:500;">{{ __('scan.mail.cta_button', [], $locale) }} &rarr;</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:32px;font-size:12px;line-height:1.6;color:{{ $muted }};">{{ __('scan.mail.footer', ['days' => config('scan.retention_days')], $locale) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
