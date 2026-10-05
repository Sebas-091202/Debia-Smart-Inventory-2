<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Renderizado de plantillas de app/Vistas.
 * El nombre de la plantilla siempre es un literal del código, nunca
 * un dato del usuario, por lo que no hay riesgo de inclusión arbitraria.
 */
final class Vista
{
    public static function mostrar(string $plantilla, array $datos = []): void
    {
        (static function (string $__archivo, array $__datos): void {
            extract($__datos, EXTR_SKIP);
            require $__archivo;
        })(APP_ROOT . '/Vistas/' . $plantilla . '.php', $datos);
    }
}
