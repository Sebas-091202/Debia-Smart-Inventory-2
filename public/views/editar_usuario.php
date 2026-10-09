<?php

declare(strict_types=1);

/** Formulario de edición de un usuario (permiso "Editar usuario"). */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Peticion;
use App\Core\Respuesta;
use App\Core\Url;
use App\Core\Vista;
use App\Dominio\Cuenta;
use App\Dominio\Permiso;
use App\Repositorios\UsuarioRepositorio;

$rol = Auth::exigirPermiso(Permiso::EditarUsuario);

$id = Peticion::consultaEntero('id');
$usuario = $id === null ? null : (new UsuarioRepositorio())->buscarPorId($id);

if ($usuario === null) {
    Flash::error('El usuario solicitado no existe.');
    Respuesta::redirigir(Url::vista('usuarios.php'));
}

if (!Auth::cuenta()->puedeGestionar(new Cuenta($usuario))) {
    Flash::error('No tienes permiso para modificar esa cuenta.');
    Respuesta::redirigir(Url::vista('usuarios.php'));
}

// Tras un error de validación se muestra lo que se había escrito.
$entradaAnterior = Flash::extraerEntrada();

Vista::mostrar('paginas/usuario_formulario', [
    'rol'     => $rol,
    'cuenta'  => Auth::cuenta(),
    'usuario' => $usuario,
    'valores' => $entradaAnterior !== [] ? $entradaAnterior : $usuario,
]);
