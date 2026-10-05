<?php

declare(strict_types=1);

/** Cambio de la propia contraseña (cualquier rol). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Vista;

Vista::mostrar('paginas/cambiar_contrasena', ['rol' => Auth::exigirSesion()]);
