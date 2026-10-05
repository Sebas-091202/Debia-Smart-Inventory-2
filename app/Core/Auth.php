<?php

declare(strict_types=1);

namespace App\Core;

use App\Dominio\Rol;
use App\Repositorios\UsuarioRepositorio;

/**
 * Estado de autenticación del usuario actual y control de acceso por rol.
 */
final class Auth
{
    private const CLAVE_SESION = 'usuario_autenticado';

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
     * el administrador lo desactiva o le cambia el rol, el cambio aplica de
     * inmediato y no al expirar la sesión.
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

        if ($usuario['rol'] !== self::datos()['rol']) {
            Sesion::poner(self::CLAVE_SESION, ['rol' => $usuario['rol']] + self::datos());
        }

        return Rol::from($usuario['rol']);
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
