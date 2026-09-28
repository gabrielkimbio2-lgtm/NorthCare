<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $initialAdmin = config('auth.initial_admin');
        $administrator = User::firstOrNew(['email' => $initialAdmin['email']]);

        if (! $administrator->exists && blank($initialAdmin['password'])) {
            $this->command?->warn('Set SYSTEM_ADMIN_PASSWORD before creating the first administrator.');

            return;
        }

        $administrator->name = $initialAdmin['name'];
        $administrator->role = UserRole::Admin;
        $administrator->account_status = 'active';

        if (filled($initialAdmin['password'])) {
            $administrator->password = $initialAdmin['password'];
        }

        $administrator->save();
    }
}
