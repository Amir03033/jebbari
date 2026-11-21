{{ $preview->client_name }} heeft de preview goedgekeurd.

Repository: {{ $preview->repo_url }} ({{ $preview->branch }})
Goedgekeurd: {{ $preview->approved_at?->timezone('Europe/Amsterdam')->format('d-m-Y H:i') }}

De preview in de admin: {{ url('/admin/previews/'.$preview->id) }}
