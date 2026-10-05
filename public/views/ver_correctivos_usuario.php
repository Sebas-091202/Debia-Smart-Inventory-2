<?php

declare(strict_types=1);

/** Historial de mantenimientos correctivos (usuario). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Dominio\Rol;
use App\Paginas\ListadoMantenimientos;

Auth::exigirRol(Rol::Usuario);
ListadoMantenimientos::mostrar(Rol::Usuario, 'Correctivo');
