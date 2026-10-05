<?php

declare(strict_types=1);

/** Formulario de registro de un equipo nuevo (solo administrador). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Vista;
use App\Dominio\Rol;

Auth::exigirRol(Rol::Admin);

Vista::mostrar('paginas/equipo_formulario', [
    'rol'     => Rol::Admin,
    'equipo'  => null,
    'valores' => Flash::extraerEntrada(),
]);
