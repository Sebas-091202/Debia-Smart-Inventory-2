<?php

declare(strict_types=1);

/** Acceso a la aplicación: inicio de sesión. */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Respuesta;
use App\Core\Url;
use App\Core\Vista;

// Con sesión iniciada no tiene sentido volver a pedir credenciales.
$rolActual = Auth::rol();
if ($rolActual !== null) {
    Respuesta::redirigir(Url::vista($rolActual->paginaInicio()));
}

Vista::mostrar('paginas/login', [
    'mensaje'         => Flash::extraer(),
    'entradaAnterior' => Flash::extraerEntrada(),
]);
