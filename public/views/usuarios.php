<?php

declare(strict_types=1);

/** Gestión de usuarios (permiso "Crear nuevo usuario" o "Editar usuario"). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Dominio\Permiso;
use App\Paginas\ListadoUsuarios;

ListadoUsuarios::mostrar(Auth::exigirPermiso(Permiso::CrearUsuario, Permiso::EditarUsuario));
