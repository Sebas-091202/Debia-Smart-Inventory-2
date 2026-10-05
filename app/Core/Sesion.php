<?php

declare(strict_types=1);

namespace App\Core;

use App\Config;

/**
 * Sesión PHP endurecida: cookie HttpOnly/SameSite, modo estricto,
 * expiración por inactividad y renovación periódica del ID.
 */
final class Sesion
{
    private const NOMBRE_COOKIE = 'DSI_SESION';
    private const CLAVE_CREADA_EN = '_creada_en';
    private const CLAVE_ULTIMA_ACTIVIDAD = '_ultima_actividad';

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        session_name(self::NOMBRE_COOKIE);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => Seguridad::esHttps(),
            'httponly' => true,
            // Lax (y no Strict) para que un enlace a la app abierto desde otra
            // aplicación (correo, chat) conserve la sesión.
            'samesite' => 'Lax',
        ]);

        session_start();

        self::expirarSiInactiva();
        self::renovarIdPeriodicamente();
    }

    /** Nuevo ID de sesión conservando los datos (usar tras login). */
    public static function regenerar(): void
    {
        session_regenerate_id(true);
        $_SESSION[self::CLAVE_CREADA_EN] = time();
    }

    /** Descarta todos los datos y emite un ID nuevo, manteniendo la sesión abierta. */
    public static function vaciar(): void
    {
        session_unset();
        self::regenerar();
    }

    public static function destruir(): void
    {
        $_SESSION = [];

        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $parametros['path'],
            'secure'   => $parametros['secure'],
            'httponly' => $parametros['httponly'],
            'samesite' => $parametros['samesite'],
        ]);

        session_destroy();
    }

    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        return $_SESSION[$clave] ?? $defecto;
    }

    public static function poner(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    /** Devuelve el valor y lo elimina (datos de un solo uso). */
    public static function extraer(string $clave, mixed $defecto = null): mixed
    {
        $valor = $_SESSION[$clave] ?? $defecto;
        unset($_SESSION[$clave]);

        return $valor;
    }

    private static function expirarSiInactiva(): void
    {
        $ultimaActividad = $_SESSION[self::CLAVE_ULTIMA_ACTIVIDAD] ?? null;

        if ($ultimaActividad !== null && time() - $ultimaActividad > Config::SESION_INACTIVIDAD_MAXIMA) {
            self::vaciar();
        }

        $_SESSION[self::CLAVE_ULTIMA_ACTIVIDAD] = time();
    }

    private static function renovarIdPeriodicamente(): void
    {
        $creadaEn = $_SESSION[self::CLAVE_CREADA_EN] ?? null;

        if ($creadaEn === null) {
            $_SESSION[self::CLAVE_CREADA_EN] = time();
        } elseif (time() - $creadaEn > Config::SESION_REGENERAR_CADA) {
            self::regenerar();
        }
    }
}
