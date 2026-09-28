<?php

namespace App\Console\Commands;

use App\Models\User;
use App\UserRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('northcare:admin:create {email} {name}')]
#[Description('Create an administrator account from the command line.')]
class CreateAdministrator extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Enter a valid administrator email address.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('An account already uses that email address.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Administrator password');
        $confirmation = (string) $this->secret('Confirm administrator password');

        if (mb_strlen($password) < 12 || ! hash_equals($password, $confirmation)) {
            $this->error('The password must be at least 12 characters and both entries must match.');

            return self::FAILURE;
        }

        $administrator = new User([
            'name' => (string) $this->argument('name'),
            'email' => $email,
            'password' => $password,
        ]);
        $administrator->role = UserRole::Admin;
        $administrator->account_status = 'active';
        $administrator->save();

        $this->info('Administrator account created.');

        return self::SUCCESS;
    }
}
