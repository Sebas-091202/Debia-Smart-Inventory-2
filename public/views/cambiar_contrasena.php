<?php

declare(strict_types=1);

/** Cambio de la propia contraseña (permiso "Cambio de contraseña"). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Vista;
use App\Dominio\Permiso;

Vista::mostrar('paginas/cambiar_contrasena', ['rol' => Auth::exigirPermiso(Permiso::CambiarContrasena)]);
