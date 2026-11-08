Nieuwe aanvraag via de website

Naam: {{ $inquiry->name }}
E-mail: {{ $inquiry->email }}
@if ($inquiry->company)
Bedrijf: {{ $inquiry->company }}
@endif
@if ($inquiry->budget)
Budget: {{ $inquiry->budget }}
@endif
Taal: {{ strtoupper($inquiry->locale) }}

Bericht:
{{ $inquiry->message }}

Beantwoorden kan door deze e-mail te beantwoorden. De aanvraag staat ook in de inbox: {{ url('/admin/inquiries/'.$inquiry->id) }}
