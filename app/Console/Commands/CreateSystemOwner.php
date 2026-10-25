<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Creates (or promotes) the system owner account that sees every institution and user.
 *   php artisan owner:create you@example.com "YourPassword"
 */
class CreateSystemOwner extends Command
{
    protected $signature = 'owner:create {email} {password} {--name=System Owner}';

    protected $description = 'Create or update the system owner account';

    public function handle(): int
    {
        $email    = strtolower(trim($this->argument('email')));
        $password = $this->argument('password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not a valid email address.');
            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Use a password of at least 8 characters.');
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first() ?? new User(['email' => $email, 'name' => $this->option('name')]);
        $user->forceFill([
            'password'        => Hash::make($password),
            'is_active'       => true,
            'is_system_owner' => true,
        ])->save();

        $this->info("System owner ready: {$email}. Log in and you'll land on the System Owner console.");

        return self::SUCCESS;
    }
}
