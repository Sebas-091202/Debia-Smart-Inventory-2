<?php

declare(strict_types=1);

/** Historial de mantenimientos preventivos (administrador). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Dominio\Rol;
use App\Paginas\ListadoMantenimientos;

Auth::exigirRol(Rol::Admin);
ListadoMantenimientos::mostrar(Rol::Admin, 'Preventivo');
