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

    /**
     * Archivo estático de public/ con su versión ("?v=<fecha de
     * modificación>"): al publicar un cambio la URL cambia y los
     * navegadores descargan el archivo nuevo en lugar de usar el guardado.
     */
    public static function recurso(string $ruta): string
    {
        $ruta = ltrim($ruta, '/');
        $modificado = @filemtime(dirname(APP_ROOT) . '/public/' . $ruta);

        return '../' . $ruta . ($modificado === false ? '' : '?v=' . $modificado);
    }

    /** "?a=1&b=2" omitiendo los valores vacíos; "" si no queda ninguno. */
    public static function cadenaConsulta(array $consulta): string
    {
        $consulta = array_filter($consulta, static fn ($valor) => $valor !== '' && $valor !== null);

        return $consulta === [] ? '' : '?' . http_build_query($consulta);
    }
}
