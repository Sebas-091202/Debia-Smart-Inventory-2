<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Construcción de rutas internas.
 * views/ y controller/ están al mismo nivel, así que "../" funciona
 * desde ambos sin depender del dominio ni de la carpeta de instalación.
 */
final class Url
{
    public static function vista(string $archivo, array $consulta = []): string
    {
        return '../views/' . $archivo . self::cadenaConsulta($consulta);
    }

    public static function controlador(string $archivo): string
    {
        return '../controller/' . $archivo;
    }

    public static function recurso(string $ruta): string
    {
        return '../' . ltrim($ruta, '/');
    }

    /** "?a=1&b=2" omitiendo los valores vacíos; "" si no queda ninguno. */
    public static function cadenaConsulta(array $consulta): string
    {
        $consulta = array_filter($consulta, static fn ($valor) => $valor !== '' && $valor !== null);

        return $consulta === [] ? '' : '?' . http_build_query($consulta);
    }
}
