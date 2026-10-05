<?php

declare(strict_types=1);

/** Edición de un usuario por parte del administrador. */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\AccionFormulario;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Peticion;
use App\Core\Url;
use App\Dominio\ErrorDeNegocio;
use App\Dominio\Rol;
use App\Formularios\UsuarioFormulario;
use App\Servicios\UsuarioServicio;

$id = Peticion::formularioEntero('id');
$urlSiFalla = $id === null ? Url::vista('usuarios.php') : Url::vista('editar_usuario.php', ['id' => $id]);

AccionFormulario::ejecutar(Rol::Admin, $urlSiFalla, static function () use ($id): string {
    if ($id === null) {
        throw new ErrorDeNegocio('Usuario no válido.');
    }

    (new UsuarioServicio())->actualizar($id, UsuarioFormulario::edicion(), Auth::id());
    Flash::exito('Usuario actualizado correctamente.');

    return Url::vista('usuarios.php');
});
