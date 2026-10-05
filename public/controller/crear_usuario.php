<?php

declare(strict_types=1);

/** Alta de un usuario por parte del administrador. */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\AccionFormulario;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Url;
use App\Dominio\Rol;
use App\Formularios\UsuarioFormulario;
use App\Servicios\UsuarioServicio;

AccionFormulario::ejecutar(Rol::Admin, Url::vista('agregar_usuario.php'), static function (): string {
    $datos = UsuarioFormulario::alta();
    (new UsuarioServicio())->crear($datos, Auth::id());

    Flash::exito("Usuario «{$datos['usuario']}» creado. Entrégale la contraseña por un canal seguro.");

    return Url::vista('usuarios.php');
});
