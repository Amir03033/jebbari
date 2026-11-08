<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** System pages cannot be created or deleted; only the owner adds or removes legal pages. */
class PagePolicy extends ContentPolicy
{
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->isOwner() && $record->kind === 'legal';
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
