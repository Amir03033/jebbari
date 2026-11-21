@php
    // E-mail clients do not read CSS variables: the light theme tokens from resources/css/app.css, written out.
    $paper = '#fafaf8';
    $card = '#f1f1ee';
    $ink = '#0b0c0e';
    $muted = '#5d5e64';
    $line = '#e2e2df';
    $signal = '#2b4ef5';
    $font = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif";
    $mono = "ui-monospace, 'SF Mono', Menlo, Consolas, monospace";
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $paper }};color:{{ $ink }};font-family:{{ $font }};">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $paper }};">
    <tr>
        <td align="center" style="padding:40px 16px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
                <tr><td style="padding-bottom:32px;font-size:20px;font-weight:600;letter-spacing:-0.03em;">jebbari<span style="color:{{ $signal }};">.</span></td></tr>
                <tr><td style="font-family:{{ $mono }};font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:{{ $muted }};padding-bottom:12px;">{{ $label }}</td></tr>
                <tr><td style="font-size:28px;line-height:1.15;font-weight:500;letter-spacing:-0.03em;padding-bottom:20px;">{{ $title }}</td></tr>
                @foreach ($paragraphs ?? [] as $paragraph)
                    <tr><td style="font-size:15px;line-height:1.6;color:{{ $muted }};padding-bottom:16px;">{{ $paragraph }}</td></tr>
                @endforeach
                @if (! empty($rows))
                    <tr>
                        <td style="padding:8px 0 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $line }};">
                                @foreach ($rows as $key => $value)
                                    <tr>
                                        <td valign="top" style="padding:12px 16px 12px 0;border-bottom:1px solid {{ $line }};font-size:14px;color:{{ $muted }};">{{ $key }}</td>
                                        <td valign="top" align="right" style="padding:12px 0;border-bottom:1px solid {{ $line }};font-size:14px;font-variant-numeric:tabular-nums;">{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                @endif
                @if (! empty($button))
                    <tr>
                        <td style="padding-bottom:36px;">
                            <a href="{{ $button['url'] }}" style="display:inline-block;background:{{ $ink }};color:{{ $paper }};text-decoration:none;font-size:15px;font-weight:500;padding:13px 22px;border-radius:999px;">{{ $button['label'] }} &rarr;</a>
                        </td>
                    </tr>
                @endif
                @if (! empty($box))
                    <tr>
                        <td style="background:{{ $card }};border:1px solid {{ $line }};border-radius:18px;padding:24px;">
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">{{ $box['text'] }}</p>
                            <a href="{{ $box['url'] }}" style="color:{{ $ink }};font-size:15px;font-weight:500;">{{ $box['label'] }} &rarr;</a>
                        </td>
                    </tr>
                @endif
                @if (! empty($footer))
                    <tr><td style="padding-top:32px;font-size:12px;line-height:1.6;color:{{ $muted }};">{{ $footer }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>
</body>
</html>
