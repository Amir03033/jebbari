<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateOwner extends Command
{
    protected $signature = 'cms:create-owner {email} {--name=Eigenaar}';

    protected $description = 'Create the owner account for /admin (an authenticator app is set up at first login)';

    public function handle(): int
    {
        $password = $this->secret('Wachtwoord (minimaal 12 tekens)');

        $validator = Validator::make(
            ['email' => $this->argument('email'), 'password' => $password],
            ['email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'string', 'min:12']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $this->option('name'),
            'email' => $this->argument('email'),
            'password' => $password,
            'role' => UserRole::Owner,
            'is_active' => true,
        ]);

        $this->info('Eigenaar aangemaakt. Bij de eerste keer inloggen stel je de authenticator-app in.');

        return self::SUCCESS;
    }
}
