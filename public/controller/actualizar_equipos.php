<?php

declare(strict_types=1);

/** Edición de un equipo (permiso "Editar equipo"). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\AccionFormulario;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Peticion;
use App\Core\Respuesta;
use App\Core\Url;
use App\Dominio\Permiso;
use App\Formularios\EquipoFormulario;
use App\Repositorios\EquipoRepositorio;
use App\Servicios\EquipoServicio;

$rol = Auth::exigirPermiso(Permiso::EditarEquipo);
$id = Peticion::formularioEntero('id');

AccionFormulario::ejecutar(null, Url::vista('editar_equipos.php', ['id' => $id]), static function () use ($id, $rol): string {
    $equipoActual = $id === null ? null : (new EquipoRepositorio())->buscarPorId($id);

    if ($equipoActual === null) {
        Respuesta::error(404, 'El equipo que intentas editar no existe.');
    }

    $datos = EquipoFormulario::desdePeticion($equipoActual);

    (new EquipoServicio())->actualizar($id, $datos, Auth::id());
    Flash::exito("Equipo {$datos['identificador']} actualizado correctamente.");

    return Url::vista($rol->vista('ver_equipos'), ['codigo' => $datos['codigo_barras']]);
});
