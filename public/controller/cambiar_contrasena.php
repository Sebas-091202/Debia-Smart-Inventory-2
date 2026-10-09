<?php

declare(strict_types=1);

/** Cambio de la propia contraseña (permiso "Cambio de contraseña"). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\AccionFormulario;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Url;
use App\Dominio\Permiso;
use App\Formularios\UsuarioFormulario;
use App\Servicios\UsuarioServicio;

$rol = Auth::exigirPermiso(Permiso::CambiarContrasena);

AccionFormulario::ejecutar(null, Url::vista('cambiar_contrasena.php'), static function () use ($rol): string {
    $datos = UsuarioFormulario::cambioContrasena();
    (new UsuarioServicio())->cambiarContrasena(Auth::id(), $datos['actual'], $datos['nueva']);

    Flash::exito('Tu contraseña se actualizó correctamente.');

    return Url::vista($rol->paginaInicio());
});
