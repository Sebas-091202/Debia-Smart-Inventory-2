<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Salidas HTTP que terminan la petición.
 * Las URL de redirección siempre se construyen internamente con Url::,
 * nunca a partir de datos del usuario (evita open redirect).
 */
final class Respuesta
{
    public static function redirigir(string $url): never
    {
        // 303 fuerza GET tras un POST (patrón Post/Redirect/Get).
        header('Location: ' . $url, true, 303);
        exit;
    }

    public static function error(int $codigo, string $mensaje): never
    {
        http_response_code($codigo);
        Vista::mostrar('paginas/error', ['codigo' => $codigo, 'mensaje' => $mensaje]);
        exit;
    }
}
