<?php

declare(strict_types=1);

/** Alta de un usuario (permiso "Crear nuevo usuario"). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\AccionFormulario;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Url;
use App\Dominio\Permiso;
use App\Formularios\UsuarioFormulario;
use App\Servicios\UsuarioServicio;

Auth::exigirPermiso(Permiso::CrearUsuario);

AccionFormulario::ejecutar(null, Url::vista('agregar_usuario.php'), static function (): string {
    $datos = UsuarioFormulario::alta();
    (new UsuarioServicio())->crear($datos, Auth::cuenta());

    Flash::exito("Usuario «{$datos['usuario']}» creado. Entrégale la contraseña por un canal seguro.");

    return Url::vista('usuarios.php');
});
