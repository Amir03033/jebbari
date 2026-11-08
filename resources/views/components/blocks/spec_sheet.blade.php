@props(['data', 'page', 'number'])

{{-- Measured values from config/specs.php only. The section is skipped while that file is empty. --}}
<section class="on-ink">
    <x-section-label :number="$number" :label="loc($data['label'] ?? null)" :meta="config('specs.measured_at')" />
    <div class="page py-16 md:py-28">
        <h2 class="t-h2 max-w-[18ch]">{{ loc($data['title'] ?? null) }}</h2>
        @if (loc($data['intro'] ?? null))
            <p class="t-body mt-6 text-muted">{{ loc($data['intro']) }}</p>
        @endif
        <dl class="mt-12">
            @foreach (config('specs.values') as $row)
                <x-spec-row :label="loc($row['label'] ?? null)" :value="$row['value'] ?? ''" :note="loc($row['note'] ?? null)" />
            @endforeach
        </dl>
    </div>
</section>
