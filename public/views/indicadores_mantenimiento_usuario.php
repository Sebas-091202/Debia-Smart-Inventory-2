<?php

declare(strict_types=1);

/** Indicadores de mantenimiento (usuario). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Dominio\Rol;
use App\Paginas\Indicadores;

Auth::exigirRol(Rol::Usuario);
Indicadores::mostrar(Rol::Usuario);
