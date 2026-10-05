<?php

declare(strict_types=1);

/**
 * Carga automática de las clases App\... y de las funciones de ayuda.
 * La usan tanto la web (bootstrap.php) como los scripts de consola (bin/).
 */

define('APP_ROOT', __DIR__);

spl_autoload_register(static function (string $clase): void {
    $prefijo = 'App\\';

    if (!str_starts_with($clase, $prefijo)) {
        return;
    }

    $ruta = APP_ROOT . '/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';

    if (is_file($ruta)) {
        require $ruta;
    }
});

require APP_ROOT . '/helpers.php';
