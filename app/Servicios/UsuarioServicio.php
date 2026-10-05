<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\BaseDatos;
use App\Dominio\ErrorDeNegocio;
use App\Dominio\Rol;
use App\Repositorios\AuditoriaRepositorio;
use App\Repositorios\UsuarioRepositorio;
use PDOException;

/**
 * Gestión de cuentas. No hay registro público: las cuentas las crea un
 * administrador, y el sistema nunca puede quedarse sin un administrador
 * activo.
 */
final class UsuarioServicio
{
    public function __construct(
        private readonly UsuarioRepositorio $usuarios = new UsuarioRepositorio(),
        private readonly AuditoriaRepositorio $auditoria = new AuditoriaRepositorio(),
    ) {
    }

    /** @throws ErrorDeNegocio si el nombre de usuario ya existe. */
    public function crear(array $datos, ?int $actorId): int
    {
        try {
            return BaseDatos::transaccion(function () use ($datos, $actorId): int {
                $id = $this->usuarios->crear(['contrasena' => self::hash($datos['contrasena'])] + $datos);
                $this->auditoria->registrar($actorId, 'CREAR USUARIO', "Usuario {$datos['usuario']} creado con rol {$datos['rol']}");

                return $id;
            });
        } catch (PDOException $excepcion) {
            if ($excepcion->getCode() === BaseDatos::VIOLACION_UNICIDAD) {
                throw new ErrorDeNegocio('Ese nombre de usuario ya está registrado.');
            }

            throw $excepcion;
        }
    }

    /** @throws ErrorDeNegocio */
    public function actualizar(int $id, array $datos, int $actorId): void
    {
        BaseDatos::transaccion(function () use ($id, $datos, $actorId): void {
            $usuario = $this->usuarios->buscarPorId($id) ?? throw new ErrorDeNegocio('El usuario ya no existe.');
            $sigueSiendoAdminActivo = $datos['activo'] && Rol::from($datos['rol'])->esAdmin();

            if ($id === $actorId && !$sigueSiendoAdminActivo) {
                throw new ErrorDeNegocio('No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.');
            }

            $adminsActivos = $this->usuarios->adminsActivosBloqueando();

            if (!$sigueSiendoAdminActivo && $adminsActivos === [$id]) {
                throw new ErrorDeNegocio('Debe quedar al menos un administrador activo.');
            }

            $this->usuarios->actualizar($id, $datos);

            if ($datos['contrasena'] !== '') {
                $this->usuarios->actualizarContrasena($id, self::hash($datos['contrasena']));
            }

            $this->auditoria->registrar($actorId, 'ACTUALIZAR USUARIO', self::describirCambios($usuario, $datos));
        });
    }

    /** @throws ErrorDeNegocio si la contraseña actual no es correcta. */
    public function cambiarContrasena(int $id, string $actual, string $nueva): void
    {
        $hashActual = $this->usuarios->hashContrasena($id) ?? throw new ErrorDeNegocio('El usuario ya no existe.');

        if (!password_verify($actual, $hashActual)) {
            throw new ErrorDeNegocio('La contraseña actual no es correcta.');
        }

        $this->usuarios->actualizarContrasena($id, self::hash($nueva));
        $this->auditoria->registrar($id, 'CAMBIAR CONTRASEÑA', 'El usuario cambió su contraseña');
    }

    private static function hash(string $contrasena): string
    {
        return password_hash($contrasena, PASSWORD_DEFAULT);
    }

    /** Resumen para la bitácora (sin datos sensibles). */
    private static function describirCambios(array $antes, array $despues): string
    {
        $cambios = [];

        if ($antes['rol'] !== $despues['rol']) {
            $cambios[] = "rol {$antes['rol']} → {$despues['rol']}";
        }

        if ((bool) $antes['activo'] !== $despues['activo']) {
            $cambios[] = $despues['activo'] ? 'activado' : 'desactivado';
        }

        if ($despues['contrasena'] !== '') {
            $cambios[] = 'contraseña restablecida';
        }

        return "Usuario {$antes['usuario']} actualizado" . ($cambios === [] ? '' : ': ' . implode(', ', $cambios));
    }
}
