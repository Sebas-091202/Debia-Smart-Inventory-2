<?php

declare(strict_types=1);

/**
 * Crea un administrador con todos los permisos desde la consola del
 * servidor. Sirve para la primera cuenta de una base nueva (con el correo
 * de App\Config::CORREO_CUENTA_PRINCIPAL se crea la cuenta principal, si
 * aún no existe) o para recuperar el acceso si nadie recuerda la
 * contraseña de un administrador (no hay registro público).
 *
 * Uso:
 *     php bin/crear_admin.php <usuario> "<Nombre completo>" <correo> <identificación>
 *
 * Se genera una contraseña aleatoria que se muestra UNA sola vez; el
 * administrador debe cambiarla al entrar (menú "Cambiar Contraseña").
 */

use App\Dominio\ErrorDeNegocio;
use App\Dominio\Permiso;
use App\Dominio\Rol;
use App\Servicios\UsuarioServicio;

if (PHP_SAPI !== 'cli') {
    exit('Solo se ejecuta desde la consola.');
}

require __DIR__ . '/../app/autoload.php';

[, $usuario, $nombre, $correo, $identificacion] = $argv + array_fill(0, 5, '');

if ($usuario === '' || $nombre === '' || $correo === '' || $identificacion === '') {
    fwrite(STDERR, "Uso: php bin/crear_admin.php <usuario> \"<Nombre completo>\" <correo> <identificación>\n");
    exit(1);
}

if (preg_match('/^[A-Za-z0-9._-]{3,50}$/', $usuario) !== 1) {
    fwrite(STDERR, "El usuario debe tener de 3 a 50 letras, números, punto, guion o guion bajo.\n");
    exit(1);
}

if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false || preg_match('/^\d{5,20}$/', $identificacion) !== 1) {
    fwrite(STDERR, "Revisa el correo y la identificación (de 5 a 20 dígitos).\n");
    exit(1);
}

$contrasena = rtrim(strtr(base64_encode(random_bytes(12)), '+/', '-_'), '=');

try {
    (new UsuarioServicio())->crear([
        'nombre'                => $nombre,
        'usuario'               => $usuario,
        'correo'                => $correo,
        'numero_identificacion' => $identificacion,
        'contrasena'            => $contrasena,
        'rol'                   => Rol::Admin->value,
        'permisos'              => Permiso::valores(Permiso::cases()),
    ], null);
} catch (ErrorDeNegocio $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}

echo "Administrador «{$usuario}» creado.\n";
echo "Contraseña temporal: {$contrasena}\n";
echo "Cámbiala al iniciar sesión (menú «Cambiar Contraseña»).\n";
