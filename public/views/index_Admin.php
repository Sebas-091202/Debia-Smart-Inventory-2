<?php

declare(strict_types=1);

/** Pantalla de inicio del rol administrador. */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Vista;
use App\Dominio\Rol;

Auth::exigirRol(Rol::Admin);
Vista::mostrar('paginas/inicio', ['rol' => Rol::Admin]);
