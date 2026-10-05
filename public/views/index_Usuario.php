<?php

declare(strict_types=1);

/** Pantalla de inicio del rol usuario. */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Vista;
use App\Dominio\Rol;

Auth::exigirRol(Rol::Usuario);
Vista::mostrar('paginas/inicio', ['rol' => Rol::Usuario]);
