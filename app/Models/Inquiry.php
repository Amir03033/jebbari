<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Models\Concerns\AuditsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquiry extends Model
{
    use AuditsChanges;

    protected $guarded = ['id'];

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
