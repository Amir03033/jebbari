@php($tz = config('site.timezone'))
@if ($p->accepted_at)
{{ $p->accepted_name }} heeft het voorstel "{{ $p->title }}" geaccepteerd op {{ $p->accepted_at->timezone($tz)->format('d-m-Y H:i') }}.
@else
{{ $p->client_name }} heeft het voorstel "{{ $p->title }}" afgewezen op {{ $p->declined_at->timezone($tz)->format('d-m-Y H:i') }}.
@if ($p->decline_reason)

Reden:
{{ $p->decline_reason }}
@endif
@endif

Klant: {{ $p->client_name }}@if ($p->client_company) ({{ $p->client_company }})@endif, {{ $p->client_email }}
Voorstel: {{ $p->url() }}
Beheer: {{ \App\Filament\Resources\Proposals\ProposalResource::getUrl('edit', ['record' => $p]) }}
