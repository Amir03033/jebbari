@php
    // E-mail clients do not read CSS variables: the light theme tokens, written out (as in mail/brand).
    $paper = '#fafaf8';
    $ink = '#0b0c0e';
    $muted = '#5d5e64';
    $line = '#e2e2df';
    $signal = '#2b4ef5';
    $font = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif";
    $mono = "ui-monospace, 'SF Mono', Menlo, Consolas, monospace";
    // Give the CMS markup readable defaults inside the mail.
    $body = strtr($body, [
        '<p>' => '<p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:'.$ink.';">',
        '<h2>' => '<h2 style="margin:28px 0 12px;font-size:22px;line-height:1.2;font-weight:500;letter-spacing:-0.02em;">',
        '<h3>' => '<h3 style="margin:24px 0 8px;font-size:18px;font-weight:500;">',
        '<ul>' => '<ul style="margin:0 0 16px;padding-left:20px;font-size:16px;line-height:1.65;">',
        '<ol>' => '<ol style="margin:0 0 16px;padding-left:20px;font-size:16px;line-height:1.65;">',
        '<a ' => '<a style="color:'.$ink.';text-decoration:underline;text-decoration-color:'.$signal.';" ',
    ]);
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $paper }};color:{{ $ink }};font-family:{{ $font }};">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $paper }};">
    <tr>
        <td align="center" style="padding:40px 16px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
                <tr><td style="padding-bottom:32px;font-size:20px;font-weight:600;letter-spacing:-0.03em;">jebbari<span style="color:{{ $signal }};">.</span></td></tr>
                <tr><td style="font-family:{{ $mono }};font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:{{ $muted }};padding-bottom:12px;">{{ __('site.newsletter.label', [], $locale) }}</td></tr>
                <tr><td style="font-size:28px;line-height:1.15;font-weight:500;letter-spacing:-0.03em;padding-bottom:24px;">{{ $subject }}</td></tr>
                <tr><td>{!! $body !!}</td></tr>
                @if ($postUrl)
                    <tr>
                        <td style="padding:12px 0 8px;">
                            <a href="{{ $postUrl }}" style="display:inline-block;background:{{ $ink }};color:{{ $paper }};text-decoration:none;font-size:15px;font-weight:500;padding:13px 22px;border-radius:999px;">{{ __('site.newsletter.mail_read', [], $locale) }} &rarr;</a>
                        </td>
                    </tr>
                @endif
                <tr>
                    <td style="padding-top:36px;border-top:1px solid {{ $line }};font-size:12px;line-height:1.6;color:{{ $muted }};">
                        {{ __('site.newsletter.mail_footer', [], $locale) }}
                        @if ($unsubscribeUrl)
                            <a href="{{ $unsubscribeUrl }}" style="color:{{ $muted }};">{{ __('site.newsletter.mail_stop', [], $locale) }}</a>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
