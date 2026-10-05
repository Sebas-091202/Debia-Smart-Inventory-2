<?php

declare(strict_types=1);

/** Formulario de edición de un equipo existente (solo administrador). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Peticion;
use App\Core\Respuesta;
use App\Core\Url;
use App\Core\Vista;
use App\Dominio\Rol;
use App\Repositorios\EquipoRepositorio;

Auth::exigirRol(Rol::Admin);

$id = Peticion::consultaEntero('id');
$equipo = $id === null ? null : (new EquipoRepositorio())->buscarPorId($id);

if ($equipo === null) {
    Flash::error($id === null ? 'Selecciona en el inventario el equipo que quieres editar.' : 'El equipo solicitado no existe.');
    Respuesta::redirigir(Url::vista('ver_equipos.php'));
}

// Tras un error de validación se muestra lo que el usuario había escrito.
$entradaAnterior = Flash::extraerEntrada();

Vista::mostrar('paginas/equipo_formulario', [
    'rol'     => Rol::Admin,
    'equipo'  => $equipo,
    'valores' => $entradaAnterior !== [] ? $entradaAnterior : $equipo,
]);
