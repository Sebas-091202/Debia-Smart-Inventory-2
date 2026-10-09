<?php

declare(strict_types=1);

/** Edición de un usuario (permiso "Editar usuario"). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\AccionFormulario;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Peticion;
use App\Core\Url;
use App\Dominio\ErrorDeNegocio;
use App\Dominio\Permiso;
use App\Formularios\UsuarioFormulario;
use App\Servicios\UsuarioServicio;

Auth::exigirPermiso(Permiso::EditarUsuario);

$id = Peticion::formularioEntero('id');
$urlSiFalla = $id === null ? Url::vista('usuarios.php') : Url::vista('editar_usuario.php', ['id' => $id]);

AccionFormulario::ejecutar(null, $urlSiFalla, static function () use ($id): string {
    if ($id === null) {
        throw new ErrorDeNegocio('Usuario no válido.');
    }

    $cuenta = Auth::cuenta();
    (new UsuarioServicio())->actualizar($id, UsuarioFormulario::edicion($cuenta->esPrincipal()), $cuenta);
    Flash::exito('Usuario actualizado correctamente.');

    return Url::vista('usuarios.php');
});
