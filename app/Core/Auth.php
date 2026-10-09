<?php

declare(strict_types=1);

namespace App\Core;

use App\Dominio\Cuenta;
use App\Dominio\Permiso;
use App\Dominio\Rol;
use App\Repositorios\UsuarioRepositorio;
use LogicException;

/**
 * Estado de autenticación del usuario actual y control de acceso por rol
 * y por permiso.
 */
final class Auth
{
    private const CLAVE_SESION = 'usuario_autenticado';

    /** Cuenta leída de la base de datos en esta petición. */
    private static ?Cuenta $cuenta = null;

    /** @param array{id: int|string, usuario: string, rol: string} $usuario */
    public static function iniciarSesion(array $usuario): void
    {
        // Nuevo ID de sesión y nuevo token CSRF al cambiar de privilegio
        // (previene fijación de sesión).
        Sesion::regenerar();
        Csrf::renovar();

        Sesion::poner(self::CLAVE_SESION, [
            'id'      => (int) $usuario['id'],
            'usuario' => $usuario['usuario'],
            'rol'     => $usuario['rol'],
        ]);
    }

    public static function cerrarSesion(): void
    {
        Sesion::destruir();
    }

    public static function id(): ?int
    {
        return self::datos()['id'] ?? null;
    }

    public static function rol(): ?Rol
    {
        return Rol::tryFrom(self::datos()['rol'] ?? '');
    }

    /**
     * Detiene la petición si no hay una sesión válida y devuelve el rol.
     *
     * El usuario se vuelve a leer de la base de datos en cada petición: si
     * lo desactivan o le cambian el rol, los permisos o el nombre de
     * usuario, el cambio aplica de inmediato y no al expirar la sesión.
     */
    public static function exigirSesion(): Rol
    {
        $id = self::id();

        if ($id === null) {
            Respuesta::redirigir(Url::vista('index_Login.php'));
        }

        $usuario = (new UsuarioRepositorio())->buscarPorId($id);

        if ($usuario === null || !$usuario['activo']) {
            Sesion::vaciar();
            Flash::guardar('error', 'Tu acceso fue desactivado. Contacta al administrador.', 'login');
            Respuesta::redirigir(Url::vista('index_Login.php'));
        }

        $enSesion = ['usuario' => $usuario['usuario'], 'rol' => $usuario['rol']];

        if (array_intersect_assoc($enSesion, self::datos()) !== $enSesion) {
            Sesion::poner(self::CLAVE_SESION, $enSesion + self::datos());
        }

        self::$cuenta = new Cuenta($usuario);

        return self::$cuenta->rol();
    }

    /**
     * Exige sesión y al menos uno de los permisos indicados; sin ellos
     * vuelve a la pantalla de inicio con un aviso.
     */
    public static function exigirPermiso(Permiso ...$permisos): Rol
    {
        $rol = self::exigirSesion();

        if (!self::cuenta()->puede(...$permisos)) {
            Flash::error('No tienes permiso para realizar esa acción.');
            Respuesta::redirigir(Url::vista($rol->paginaInicio()));
        }

        return $rol;
    }

    /** Cuenta del usuario actual; disponible después de exigirSesion(). */
    public static function cuenta(): Cuenta
    {
        return self::$cuenta ?? throw new LogicException('Auth::cuenta() requiere llamar antes a exigirSesion().');
    }

    /** Detiene la petición si no hay sesión o el rol no corresponde. */
    public static function exigirRol(Rol $rolRequerido): void
    {
        $rolActual = self::exigirSesion();

        if ($rolActual !== $rolRequerido) {
            Respuesta::redirigir(Url::vista($rolActual->paginaInicio()));
        }
    }

    private static function datos(): array
    {
        $datos = Sesion::obtener(self::CLAVE_SESION);

        return is_array($datos) ? $datos : [];
    }
}
