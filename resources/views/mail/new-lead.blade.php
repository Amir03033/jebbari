@php
    use App\Leads\PriceCalculator;
    $w = $lead->choice('wizard', []);
@endphp
{{ __('leads.owner.subject_'.$event, ['name' => $lead->name ?: $lead->email], 'nl') }}

Naam: {{ $lead->name }}
E-mail: {{ $lead->email }}
@if ($lead->phone)
Telefoon: {{ $lead->phone }}
@endif
@if ($lead->company)
Bedrijf: {{ $lead->company }}
@endif
Bron: {{ \App\Models\Lead::SOURCES[$lead->source] ?? $lead->source }}
Taal: {{ strtoupper($lead->locale) }}
@if ($range = PriceCalculator::range($lead->estimate_min, $lead->estimate_max))
Indicatie calculator: {{ $range }}
@endif
@if ($upcoming = $lead->appointments->firstWhere('status', 'confirmed'))
Afspraak: {{ \App\Mail\AppointmentFormat::when($upcoming, 'nl') }} ({{ loc($upcoming->type->name, 'nl') }})
@endif
@foreach (['summary' => 'Wat', 'goal' => 'Doel', 'audience' => 'Doelgroep', 'budget' => 'Budget', 'deadline' => 'Deadline', 'features' => 'Functies'] as $key => $label)
@if (! empty($w[$key]))
{{ $label }}: {{ $w[$key] }}
@endif
@endforeach
@if (! empty($w['examples']))
Voorbeelden: {{ implode(', ', $w['examples']) }}
@endif

Alles staat in de admin: {{ url('/admin/leads/'.$lead->id) }}
