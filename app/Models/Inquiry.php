<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Models\Concerns\AuditsChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquiry extends Model
{
    use AuditsChanges;
    use Prunable;

    protected $guarded = ['id'];

    /** Contact messages go config('leads.contact_months') after the last activity (message or note), as the privacy statement promises. */
    public function prunable(): Builder
    {
        $cutoff = now()->subMonths(config('leads.contact_months'));

        return static::query()->where('updated_at', '<', $cutoff)
            ->whereDoesntHave('notes', fn (Builder $n) => $n->where('created_at', '>=', $cutoff));
    }

    protected function casts(): array
    {
        return ['status' => InquiryStatus::class, 'read_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function notes(): HasMany
    {
        return $this->hasMany(InquiryNote::class)->latest();
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markRead(): void
    {
        if ($this->isUnread()) {
            $this->update(['read_at' => now(), 'status' => $this->status === InquiryStatus::New ? InquiryStatus::InProgress : $this->status]);
        }
    }

    public function markCompleted(): void
    {
        $this->update(['status' => InquiryStatus::Completed, 'completed_at' => now(), 'read_at' => $this->read_at ?? now()]);
    }
}
