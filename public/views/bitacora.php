<?php

declare(strict_types=1);

/** Bitácora de cambios (permiso "Ver bitácora de cambios"). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Dominio\Permiso;
use App\Paginas\Bitacora;

Bitacora::mostrar(Auth::exigirPermiso(Permiso::VerBitacora));
