<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Every panel role can read and work inquiries; only the owner deletes them. */
class InquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageInbox();
    }

    public function view(User $user, Model $record): bool
    {
        return $user->canManageInbox();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return $user->canManageInbox();
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->isOwner();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isOwner();
    }
}
