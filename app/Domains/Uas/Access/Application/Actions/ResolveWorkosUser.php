<?php

namespace App\Domains\Uas\Access\Application\Actions;

use App\Domains\Uas\Access\Domain\DTOs\WorkosIdentity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ResolveWorkosUser
{
    public function handle(WorkosIdentity $identity, ?User $linkTo = null): User
    {
        if (! $identity->emailVerified || ! filter_var($identity->email, FILTER_VALIDATE_EMAIL)
            || strlen($identity->email) > 255) {
            throw ValidationException::withMessages(['workos' => 'Verify your email address in WorkOS before continuing.']);
        }

        return DB::transaction(function () use ($identity, $linkTo) {
            $user = User::where('workos_id', $identity->id)->lockForUpdate()->first();
            $email = Str::lower($identity->email);

            if ($linkTo) {
                $local = User::whereKey($linkTo->id)->lockForUpdate()->firstOrFail();

                if (($user && ! $user->is($local))
                    || ($local->workos_id && $local->workos_id !== $identity->id)
                    || Str::lower($local->email) !== $email) {
                    throw ValidationException::withMessages(['workos' => 'Use the WorkOS account with the same email address as your app account. This identity must not already be connected elsewhere.']);
                }

                $local->forceFill(['workos_id' => $identity->id, 'email_verified_at' => $local->email_verified_at ?? now()])->save();

                return $local;
            }

            if ($user) {
                // Stable provider ID identifies the account. Roles and local profile stay local.
                return $user;
            }

            if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                throw ValidationException::withMessages(['workos' => 'An app account already uses this email. Log in with your password, then connect WorkOS in Profile settings.']);
            }

            $user = new User;
            $user->forceFill([
                'name' => Str::limit($identity->name, 255, ''),
                'email' => $email,
                'email_verified_at' => now(),
                'workos_id' => $identity->id,
                'password' => Str::random(64),
                'role' => 'user',
            ])->save();

            return $user;
        });
    }
}
