<?php

declare(strict_types=1);

/**
 * Punto de arranque único.
 *
 * Toda vista y todo controlador lo incluye como primera instrucción, de modo
 * que la configuración de errores, las cabeceras de seguridad y la sesión
 * segura se aplican siempre, sin depender de que cada archivo lo recuerde.
 */

use App\Config;
use App\Core\Respuesta;
use App\Core\Seguridad;
use App\Core\Sesion;

require __DIR__ . '/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', Config::esDesarrollo() ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set(Config::zonaHoraria());

// Cualquier error no previsto se registra completo en el log del servidor,
// pero el usuario solo ve un mensaje genérico (sin rutas, SQL ni trazas).
set_exception_handler(static function (Throwable $excepcion): void {
    error_log((string) $excepcion);

    if (Config::esDesarrollo()) {
        throw $excepcion;
    }

    Respuesta::error(500, 'Ocurrió un error inesperado. Intenta de nuevo más tarde.');
});

Seguridad::enviarCabeceras();
Sesion::iniciar();
