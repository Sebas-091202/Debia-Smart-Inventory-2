<?php

declare(strict_types=1);

/** Inicio de sesión. */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\AccionFormulario;
use App\Core\Auth;
use App\Core\Peticion;
use App\Core\Seguridad;
use App\Core\Url;
use App\Dominio\Rol;
use App\Servicios\AutenticacionServicio;

AccionFormulario::ejecutar(null, Url::vista('index_Login.php'), static function (): string {
    $usuario = (new AutenticacionServicio())->autenticar(
        Peticion::formularioExacto('usuario'),
        Peticion::formularioExacto('contrasena'),
        Seguridad::ipCliente()
    );

    Auth::iniciarSesion($usuario);

    return Url::vista(Rol::from($usuario['rol'])->paginaInicio());
}, contextoError: 'login');
