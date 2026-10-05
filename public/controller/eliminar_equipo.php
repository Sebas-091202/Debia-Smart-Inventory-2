<?php

declare(strict_types=1);

/**
 * Eliminación de un equipo (solo administrador).
 * Antes vivía dentro de views/ver_equipos.php; ahora es un controlador
 * propio para que la vista solo muestre datos.
 */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\AccionFormulario;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Peticion;
use App\Core\Url;
use App\Dominio\ErrorDeNegocio;
use App\Dominio\Rol;
use App\Servicios\EquipoServicio;

AccionFormulario::ejecutar(Rol::Admin, Url::vista('ver_equipos.php'), static function (): string {
    $id = Peticion::formularioEntero('id_equipo') ?? throw new ErrorDeNegocio('Identificador de equipo inválido.');

    (new EquipoServicio())->eliminar($id, Auth::id());
    Flash::exito('Equipo eliminado correctamente.');

    return Url::vista('ver_equipos.php');
});
