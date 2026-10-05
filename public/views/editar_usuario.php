<?php

declare(strict_types=1);

/** Formulario de edición de un usuario (solo administrador). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Peticion;
use App\Core\Respuesta;
use App\Core\Url;
use App\Core\Vista;
use App\Dominio\Rol;
use App\Repositorios\UsuarioRepositorio;

Auth::exigirRol(Rol::Admin);

$id = Peticion::consultaEntero('id');
$usuario = $id === null ? null : (new UsuarioRepositorio())->buscarPorId($id);

if ($usuario === null) {
    Flash::error('El usuario solicitado no existe.');
    Respuesta::redirigir(Url::vista('usuarios.php'));
}

// Tras un error de validación se muestra lo que el administrador había escrito.
$entradaAnterior = Flash::extraerEntrada();

Vista::mostrar('paginas/usuario_formulario', [
    'rol'      => Rol::Admin,
    'usuario'  => $usuario,
    'valores'  => $entradaAnterior !== [] ? $entradaAnterior : $usuario,
    'esPropio' => (int) $usuario['id'] === Auth::id(),
]);
