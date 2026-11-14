@props(['data', 'page', 'number'])

{{-- Measured values from config/specs.php only. The section is skipped while that file is empty. --}}
<section class="section-tight">
    <div class="panel on-ink">
    <div class="page py-14 md:py-24">
        <p class="t-label label-row mb-10 flex justify-between gap-6"><span><span class="text-muted">{{ $number }}</span> / {{ loc($data['label'] ?? null) }}</span><span class="text-muted">{{ config('specs.measured_at') }}</span></p>
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
    </div>
</section>
