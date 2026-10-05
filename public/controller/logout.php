<?php

declare(strict_types=1);

/**
 * Cierre de sesión. Es POST con token CSRF para que un enlace o imagen
 * de otro sitio no pueda cerrar la sesión del usuario.
 */

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Peticion;
use App\Core\Respuesta;
use App\Core\Url;

Peticion::exigirPost();

// Si la sesión ya expiró no hay nada que proteger ni token que comparar.
if (Auth::rol() !== null) {
    Csrf::verificar();
    Auth::cerrarSesion();
}

Respuesta::redirigir(Url::vista('index_Login.php'));
