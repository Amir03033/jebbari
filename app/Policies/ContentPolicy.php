<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Content (services, projects, blog, ...): owners and editors create, edit and
 * publish; only owners delete or restore.
 */
class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canEditContent();
    }

    public function view(User $user, Model $record): bool
    {
        return $user->canEditContent();
    }

    public function create(User $user): bool
    {
        return $user->canEditContent();
    }

    public function update(User $user, Model $record): bool
    {
        return $user->canEditContent();
    }

    public function reorder(User $user): bool
    {
        return $user->canEditContent();
    }

    public function publish(User $user, Model $record): bool
    {
        return $user->canEditContent();
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->isOwner();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function restore(User $user, Model $record): bool
    {
        return $user->isOwner();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return $user->isOwner();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isOwner();
    }
}
