<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        /**
         * Token de CSRF caducado: en vez de la pantalla "Page Expired" se
         * vuelve a la pantalla de origen con un aviso utilizable.
         *
         * Pasa cuando el pasante deja el formulario abierto y su sesion cambia
         * (se regenera al iniciar sesion de administrador, o simplemente caduca).
         * El token que viaja en el HTML deja de valer y el envio se corta antes
         * de llegar al controlador, por eso el marcado no deja rastro en el log.
         *
         * Se comprueba por mensaje y no solo por clase porque el middleware de
         * este Laravel lanza varias excepciones distintas para el mismo 419
         * (token caducado, origen no permitido) y todas necesitan el mismo trato.
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            $esToken = $e instanceof TokenMismatchException
                || in_array($e->getMessage(), [
                    'CSRF token mismatch.',
                    'CSRF token expired.',
                    'Origin mismatch.',
                ], true);

            if (! $esToken) {
                return null;
            }

            Log::warning('peticion rechazada por token CSRF caducado', [
                'ruta' => $request->path(),
                'metodo' => $request->method(),
                'agente' => $request->userAgent(),
                'origen' => $request->headers->get('referer'),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La página quedó abierta demasiado tiempo. Recarga e inténtalo de nuevo.',
                ], 419);
            }

            $origen = $request->headers->get('referer');
            $mismo = $origen && rtrim($origen, '/') === rtrim($request->fullUrl(), '/');

            // Nunca se vuelve a la misma peticion que se acaba de rechazar: se
            // cae a la pantalla de marcado, que es de donde sale el formulario.
            if ($mismo || ! $origen) {
                $destino = $request->is('asistencias/*')
                    ? route('asistencias.registrar')
                    : url()->previous();
            } else {
                $destino = $origen;
            }

            return redirect($destino)->with(
                'error',
                'La página estuvo abierta demasiado tiempo y se venció la seguridad. '
                .'Vuelve a cargarla e inténtalo otra vez; no se guardó nada.'
            );
        });
    })->create();
