<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Users, navigation, audit log: owner only. */
class OwnerOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, Model $record): bool
    {
        return $user->isOwner();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Model $record): bool
    {
        return $user->isOwner();
    }

    public function reorder(User $user): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, Model $record): bool
    {
        // Nobody deletes their own account.
        return $user->isOwner() && ! $record->is($user);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isOwner();
    }
}
