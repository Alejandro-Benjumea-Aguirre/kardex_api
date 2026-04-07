<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendVerificationEmail(User $user, string $token): void
    {
        $url = url("/api/v1/auth/verify-email/{$user->id}/{$token}");

        Mail::raw(
            "Hola {$user->first_name},\n\nVerificá tu email haciendo clic en este link:\n{$url}\n\nEl link expira en 24 horas.",
            function ($message) use ($user) {
                $message->to($user->email, "{$user->first_name} {$user->last_name}")
                        ->subject('Verificá tu cuenta');
            }
        );
    }

    public function sendPasswordResetEmail(User $user, string $token): void
    {
        $url = url("/reset-password?token={$token}&email={$user->email}");

        Mail::raw(
            "Hola {$user->first_name},\n\nRecibimos una solicitud para restablecer tu contraseña.\n\nUsá este link:\n{$url}\n\nExpira en 1 hora. Si no lo solicitaste, ignorá este email.",
            function ($message) use ($user) {
                $message->to($user->email, "{$user->first_name} {$user->last_name}")
                        ->subject('Restablecer contraseña');
            }
        );
    }
}
