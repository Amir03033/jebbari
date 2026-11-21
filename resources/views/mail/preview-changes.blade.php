{{ $preview->client_name }} vraagt om wijzigingen in de preview.

Repository: {{ $preview->repo_url }} ({{ $preview->branch }})
Gevraagd: {{ $preview->change_requested_at?->timezone('Europe/Amsterdam')->format('d-m-Y H:i') }}

Wijzigingen:
{{ $preview->change_request }}

De preview in de admin: {{ url('/admin/previews/'.$preview->id) }}
