<?php

declare(strict_types=1);

namespace App\Core;

use App\Config;

/**
 * Cabeceras HTTP de seguridad comunes a todas las respuestas.
 *
 * Como todo el CSS y el JS viven en archivos propios, el CSP no necesita
 * 'unsafe-inline' ni nonces: cualquier <script> o <style> inyectado en la
 * página es bloqueado por el navegador.
 */
final class Seguridad
{
    private const POLITICA_CONTENIDO = [
        "default-src 'self'",
        "script-src 'self'",
        "style-src 'self' https://fonts.googleapis.com https://unpkg.com",
        "font-src 'self' https://fonts.gstatic.com https://unpkg.com data:",
        "img-src 'self' data:",
        "connect-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
    ];

    public static function enviarCabeceras(): void
    {
        header_remove('X-Powered-By');

        header('Content-Security-Policy: ' . implode('; ', self::POLITICA_CONTENIDO));
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('Cross-Origin-Opener-Policy: same-origin');

        // Las páginas son dinámicas y con datos internos: nunca se guardan en
        // caché, así "Atrás" tras cerrar sesión no muestra información.
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if (self::esHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function esHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        // X-Forwarded-Proto lo puede enviar cualquier cliente: solo se
        // considera cuando la app está explícitamente detrás de un proxy.
        return Config::confiarEnProxy()
            && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public static function ipCliente(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
