<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Owners and editors manage media. Deleting is owner-only and blocked while an asset is used. */
class MediaAssetPolicy extends ContentPolicy
{
    public function delete(User $user, Model $record): bool
    {
        return $user->isOwner() && $record instanceof MediaAsset && $record->usages() === [];
    }
}
