<?php

declare(strict_types=1);

/** Formulario de alta de un usuario (permiso "Crear nuevo usuario"). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Vista;
use App\Dominio\Permiso;

$rol = Auth::exigirPermiso(Permiso::CrearUsuario);

Vista::mostrar('paginas/usuario_formulario', [
    'rol'     => $rol,
    'cuenta'  => Auth::cuenta(),
    'usuario' => null,
    'valores' => Flash::extraerEntrada(),
]);
