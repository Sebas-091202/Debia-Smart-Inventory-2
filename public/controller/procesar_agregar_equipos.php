<?php

declare(strict_types=1);

/** Alta de un equipo (solo administrador). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\AccionFormulario;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Url;
use App\Dominio\Rol;
use App\Formularios\EquipoFormulario;
use App\Servicios\EquipoServicio;

AccionFormulario::ejecutar(Rol::Admin, Url::vista('agregar_equipos.php'), static function (): string {
    $datos = EquipoFormulario::desdePeticion();

    (new EquipoServicio())->crear($datos, Auth::id());
    Flash::exito("Equipo {$datos['identificador']} registrado correctamente.");

    return Url::vista('ver_equipos.php', ['codigo' => $datos['codigo_barras']]);
});
