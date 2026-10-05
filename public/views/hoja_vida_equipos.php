<?php

declare(strict_types=1);

/** Hoja de vida de equipos (administrador: incluye registro de mantenimientos). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Dominio\Rol;
use App\Paginas\HojaVida;

Auth::exigirRol(Rol::Admin);
HojaVida::mostrar(Rol::Admin);
