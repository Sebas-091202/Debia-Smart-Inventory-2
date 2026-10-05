<?php

declare(strict_types=1);

/** Inventario de equipos (usuario, solo lectura). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Dominio\Rol;
use App\Paginas\ListadoEquipos;

Auth::exigirRol(Rol::Usuario);
ListadoEquipos::mostrar(Rol::Usuario);
