<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('marche:make-admin {email : Adresse électronique de l’administrateur}')]
#[Description('Promeut un utilisateur administrateur, ou crée le compte s’il n’existe pas')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $email = Str::lower(trim($this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->error(__('commands.make_admin.invalid_email'));

            return self::FAILURE;
        }

        $user = User::firstWhere('email', $email);

        if ($user === null) {
            $user = new User([
                'first_name' => $this->ask(__('commands.make_admin.first_name')),
                'last_name' => $this->ask(__('commands.make_admin.last_name')),
                'email' => $email,
                'locale' => config('app.locale'),
                'password' => $this->secret(__('commands.make_admin.password')),
            ]);
            $user->email_verified_at = now();
        }

        $user->role = Role::Admin;
        $user->save();

        $this->info(__('commands.make_admin.done', ['email' => $email]));

        return self::SUCCESS;
    }
}
