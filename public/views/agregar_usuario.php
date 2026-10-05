<?php

declare(strict_types=1);

/** Formulario de alta de un usuario (solo administrador). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Vista;
use App\Dominio\Rol;

Auth::exigirRol(Rol::Admin);

Vista::mostrar('paginas/usuario_formulario', [
    'rol'      => Rol::Admin,
    'usuario'  => null,
    'valores'  => Flash::extraerEntrada(),
    'esPropio' => false,
]);
