<?php

declare(strict_types=1);

/** Historial de inicios de sesión (permiso "Ver inicios de sesión"). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Dominio\Permiso;
use App\Paginas\IniciosSesion;

IniciosSesion::mostrar(Auth::exigirPermiso(Permiso::VerIniciosSesion));
