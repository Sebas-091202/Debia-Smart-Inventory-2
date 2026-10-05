<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Protección contra Cross-Site Request Forgery con token sincronizado.
 * Todo formulario POST incluye campo_csrf() y todo controlador que
 * modifique datos llama a Csrf::verificar() antes de actuar.
 */
final class Csrf
{
    private const CLAVE_SESION = '_csrf_token';
    private const CAMPO = 'csrf_token';

    public static function token(): string
    {
        $token = Sesion::obtener(self::CLAVE_SESION);

        if (!is_string($token) || $token === '') {
            $token = self::renovar();
        }

        return $token;
    }

    /** Genera un token nuevo (al cambiar el nivel de privilegio). */
    public static function renovar(): string
    {
        $token = bin2hex(random_bytes(32));
        Sesion::poner(self::CLAVE_SESION, $token);

        return $token;
    }

    public static function verificar(): void
    {
        $enviado = $_POST[self::CAMPO] ?? '';
        $esperado = Sesion::obtener(self::CLAVE_SESION);

        if (!is_string($enviado) || !is_string($esperado) || !hash_equals($esperado, $enviado)) {
            Respuesta::error(403, 'El formulario expiró o no es válido. Recarga la página e inténtalo de nuevo.');
        }
    }
}
