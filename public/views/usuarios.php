<?php

declare(strict_types=1);

/** Gestión de usuarios (solo administrador). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Vista;
use App\Dominio\Rol;
use App\Repositorios\UsuarioRepositorio;

Auth::exigirRol(Rol::Admin);

Vista::mostrar('paginas/usuarios_listado', [
    'rol'      => Rol::Admin,
    'usuarios' => (new UsuarioRepositorio())->listar(),
    'actualId' => Auth::id(),
]);
