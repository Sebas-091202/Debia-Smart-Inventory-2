<?php

declare(strict_types=1);

/** Registro de un mantenimiento desde la hoja de vida (solo administrador). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\AccionFormulario;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Peticion;
use App\Core\Url;
use App\Dominio\Rol;
use App\Formularios\MantenimientoFormulario;
use App\Servicios\MantenimientoServicio;

$urlHojaVida = Url::vista('hoja_vida_equipos.php', ['id' => Peticion::formularioEntero('equipo_id')]);

AccionFormulario::ejecutar(Rol::Admin, $urlHojaVida, static function () use ($urlHojaVida): string {
    $mantenimiento = MantenimientoFormulario::mantenimiento();

    (new MantenimientoServicio())->registrar($mantenimiento, MantenimientoFormulario::repuestos(), Auth::id());
    Flash::exito('Mantenimiento registrado correctamente.');

    return $urlHojaVida;
});
