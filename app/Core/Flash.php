<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Mensajes de un solo uso entre una redirección y la página siguiente,
 * más la entrada del formulario para volver a rellenarlo tras un error.
 * Sustituye los antiguos alert() en línea, que el CSP bloquearía.
 */
final class Flash
{
    private const CLAVE_MENSAJE = '_flash_mensaje';
    private const CLAVE_ENTRADA = '_flash_entrada';

    public static function exito(string $mensaje): void
    {
        self::guardar('exito', $mensaje);
    }

    public static function error(string $mensaje): void
    {
        self::guardar('error', $mensaje);
    }

    /** @return array{tipo: string, mensaje: string, contexto: string}|null */
    public static function extraer(): ?array
    {
        $mensaje = Sesion::extraer(self::CLAVE_MENSAJE);

        return is_array($mensaje) ? $mensaje : null;
    }

    /** Contexto opcional (p. ej. qué panel del login mostrar). */
    public static function guardar(string $tipo, string $mensaje, string $contexto = ''): void
    {
        Sesion::poner(self::CLAVE_MENSAJE, ['tipo' => $tipo, 'mensaje' => $mensaje, 'contexto' => $contexto]);
    }

    public static function guardarEntrada(array $entrada): void
    {
        Sesion::poner(self::CLAVE_ENTRADA, $entrada);
    }

    public static function extraerEntrada(): array
    {
        $entrada = Sesion::extraer(self::CLAVE_ENTRADA, []);

        return is_array($entrada) ? $entrada : [];
    }
}
