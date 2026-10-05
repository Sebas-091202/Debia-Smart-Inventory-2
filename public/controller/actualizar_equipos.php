<?php

declare(strict_types=1);

/** Edición de un equipo (solo administrador). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\AccionFormulario;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Peticion;
use App\Core\Respuesta;
use App\Core\Url;
use App\Dominio\Rol;
use App\Formularios\EquipoFormulario;
use App\Repositorios\EquipoRepositorio;
use App\Servicios\EquipoServicio;

$id = Peticion::formularioEntero('id');

AccionFormulario::ejecutar(Rol::Admin, Url::vista('editar_equipos.php', ['id' => $id]), static function () use ($id): string {
    $equipoActual = $id === null ? null : (new EquipoRepositorio())->buscarPorId($id);

    if ($equipoActual === null) {
        Respuesta::error(404, 'El equipo que intentas editar no existe.');
    }

    $datos = EquipoFormulario::desdePeticion($equipoActual);

    (new EquipoServicio())->actualizar($id, $datos, Auth::id());
    Flash::exito("Equipo {$datos['identificador']} actualizado correctamente.");

    return Url::vista('ver_equipos.php', ['codigo' => $datos['codigo_barras']]);
});
