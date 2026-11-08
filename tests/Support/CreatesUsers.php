<?php

namespace Tests\Support;

use App\Enums\UserRole;
use App\Models\User;

trait CreatesUsers
{
    /** A panel user with an authenticator secret, so the required-MFA gate lets them in. */
    protected function userWithRole(UserRole $role, array $attributes = []): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
            'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
            ...$attributes,
        ]);
    }

    protected function owner(): User
    {
        return $this->userWithRole(UserRole::Owner);
    }

    protected function editor(): User
    {
        return $this->userWithRole(UserRole::Editor);
    }

    protected function support(): User
    {
        return $this->userWithRole(UserRole::Support);
    }
}
