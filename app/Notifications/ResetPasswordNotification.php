<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de restablecimiento de contraseña para administradores.
 *
 * Extiende la notificación de Laravel para conservar el token y la URL de
 * restablecimiento, pero reemplaza el texto en inglés por uno en español y
 * saluda al administrador por su nombre.
 */
class ResetPasswordNotification extends ResetPassword
{
    /**
     * Construye la representación del correo.
     *
     * @param  mixed  $notifiable
     * @return MailMessage|Mailable
     */
    public function toMail($notifiable)
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $minutos = (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire'
        );

        return (new MailMessage)
            ->subject('Restablece tu contraseña')
            ->greeting('Hola, '.$notifiable->name.'!')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta en el Sistema de Pasantes.')
            ->action('Restablecer contraseña', $this->resetUrl($notifiable))
            ->line('Este enlace caducará en '.$minutos.' minutos.')
            ->line('Si no solicitaste este cambio, puedes ignorar este correo: tu contraseña actual seguirá funcionando.')
            ->salutation('Saludos, Sistema de Pasantes');
    }
}
