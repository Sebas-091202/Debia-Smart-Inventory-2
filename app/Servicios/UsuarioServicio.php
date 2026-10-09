<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\BaseDatos;
use App\Dominio\Cuenta;
use App\Dominio\ErrorDeNegocio;
use App\Dominio\Permiso;
use App\Dominio\Rol;
use App\Repositorios\AuditoriaRepositorio;
use App\Repositorios\UsuarioRepositorio;
use PDOException;

/**
 * Gestión de cuentas. No hay registro público: las cuentas las crea la
 * cuenta principal o quien tenga el permiso "Crear nuevo usuario".
 *
 * Reglas (además de las de App\Dominio\Cuenta):
 *   - solo la cuenta principal asigna permisos y cambia nombres de usuario;
 *   - su correo está reservado: ninguna otra cuenta puede tomarlo;
 *   - nadie cambia su propio rol, se desactiva ni restablece su propia
 *     contraseña sin conocer la actual;
 *   - el sistema nunca se queda sin un administrador activo.
 */
final class UsuarioServicio
{
    public function __construct(
        private readonly UsuarioRepositorio $usuarios = new UsuarioRepositorio(),
        private readonly AuditoriaRepositorio $auditoria = new AuditoriaRepositorio(),
    ) {
    }

    /**
     * @param Cuenta|null $actor Quien crea la cuenta; null = consola del
     *                           servidor (bin/crear_admin.php), que puede
     *                           crear la cuenta principal si aún no existe.
     * @throws ErrorDeNegocio
     */
    public function crear(array $datos, ?Cuenta $actor): int
    {
        if ($actor !== null) {
            self::exigirRolAsignable($actor, $datos['rol']);

            if (!$actor->esPrincipal()) {
                $datos['permisos'] = Permiso::valores(Permiso::PREDETERMINADOS);
            }
        }

        if (Cuenta::esCorreoPrincipal($datos['correo']) && ($actor !== null || $this->usuarios->existeCorreo($datos['correo']))) {
            throw new ErrorDeNegocio('Ese correo está reservado para la cuenta principal del sistema.');
        }

        return self::traducirDuplicado(fn (): int => BaseDatos::transaccion(function () use ($datos, $actor): int {
            $id = $this->usuarios->crear(['contrasena' => self::hash($datos['contrasena'])] + $datos);
            $this->auditoria->registrar(
                $actor?->id(),
                'CREAR USUARIO',
                "Usuario {$datos['usuario']} creado con rol {$datos['rol']} y permisos: " . self::listarPermisos($datos['permisos'])
            );

            return $id;
        }));
    }

    /** @throws ErrorDeNegocio */
    public function actualizar(int $id, array $datos, Cuenta $actor): void
    {
        self::traducirDuplicado(fn () => BaseDatos::transaccion(function () use ($id, $datos, $actor): void {
            $usuario = $this->usuarios->buscarPorId($id) ?? throw new ErrorDeNegocio('El usuario ya no existe.');
            $objetivo = new Cuenta($usuario);

            if (!$actor->puedeGestionar($objetivo)) {
                throw new ErrorDeNegocio('No tienes permiso para modificar esa cuenta.');
            }

            $final = self::resolverCambios($usuario, $objetivo, $datos, $actor);
            $sigueSiendoAdminActivo = $final['activo'] && Rol::from($final['rol'])->esAdmin();

            if (!$sigueSiendoAdminActivo && $this->usuarios->adminsActivosBloqueando() === [$id]) {
                throw new ErrorDeNegocio('Debe quedar al menos un administrador activo.');
            }

            $this->usuarios->actualizar($id, $final);

            if ($final['contrasena'] !== '') {
                $this->usuarios->actualizarContrasena($id, self::hash($final['contrasena']));
            }

            $this->auditoria->registrar($actor->id(), 'ACTUALIZAR USUARIO', self::describirCambios($usuario, $final));
        }));
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

    /**
     * Combina lo enviado con lo guardado según lo que el actor puede
     * cambiar. Lo que no le corresponde conserva el valor guardado; si
     * intenta algo prohibido de forma explícita, se rechaza.
     *
     * @throws ErrorDeNegocio
     */
    private static function resolverCambios(array $usuario, Cuenta $objetivo, array $datos, Cuenta $actor): array
    {
        $esPropia = $objetivo->id() === $actor->id();

        if ($esPropia && ($datos['rol'] !== $usuario['rol'] || !$datos['activo'])) {
            throw new ErrorDeNegocio('No puedes cambiar tu propio rol ni desactivar tu cuenta.');
        }

        if ($esPropia && $datos['contrasena'] !== '') {
            throw new ErrorDeNegocio('Para cambiar tu propia contraseña usa la opción «Cambiar Contraseña».');
        }

        if ($objetivo->esPrincipal() && strcasecmp($datos['correo'], (string) $usuario['correo']) !== 0) {
            throw new ErrorDeNegocio('El correo de la cuenta principal no se puede cambiar.');
        }

        if (!$objetivo->esPrincipal() && Cuenta::esCorreoPrincipal($datos['correo'])) {
            throw new ErrorDeNegocio('Ese correo está reservado para la cuenta principal del sistema.');
        }

        if (!$esPropia) {
            self::exigirRolAsignable($actor, $datos['rol']);
        }

        $asignaPermisos = $actor->esPrincipal() && !$objetivo->esPrincipal();

        return [
            'nombre'                => $datos['nombre'],
            'usuario'               => $actor->esPrincipal() ? $datos['usuario'] : $usuario['usuario'],
            'correo'                => $objetivo->esPrincipal() ? $usuario['correo'] : $datos['correo'],
            'numero_identificacion' => $datos['numero_identificacion'],
            'rol'                   => $datos['rol'],
            'activo'                => $datos['activo'],
            'permisos'              => $asignaPermisos ? $datos['permisos'] : Cuenta::valoresDePermisos($usuario['permisos']),
            'contrasena'            => $datos['contrasena'],
        ];
    }

    /** @throws ErrorDeNegocio */
    private static function exigirRolAsignable(Cuenta $actor, string $rol): void
    {
        if (!in_array(Rol::from($rol), $actor->rolesAsignables(), true)) {
            throw new ErrorDeNegocio('No puedes asignar un rol superior al tuyo.');
        }
    }

    /**
     * El nombre de usuario es único (sin distinguir mayúsculas).
     *
     * @template T
     * @param  callable(): T $operacion
     * @return T
     * @throws ErrorDeNegocio
     */
    private static function traducirDuplicado(callable $operacion): mixed
    {
        try {
            return $operacion();
        } catch (PDOException $excepcion) {
            if ($excepcion->getCode() === BaseDatos::VIOLACION_UNICIDAD) {
                throw new ErrorDeNegocio('Ese nombre de usuario ya está registrado.');
            }

            throw $excepcion;
        }
    }

    private static function hash(string $contrasena): string
    {
        return password_hash($contrasena, PASSWORD_DEFAULT);
    }

    /** @param string[] $valores */
    private static function listarPermisos(array $valores): string
    {
        $etiquetas = array_map(static fn (Permiso $permiso) => $permiso->etiqueta(), Permiso::desdeValores($valores));

        return $etiquetas === [] ? 'ninguno' : implode(', ', $etiquetas);
    }

    /** Resumen para la bitácora (sin datos sensibles). */
    private static function describirCambios(array $antes, array $despues): string
    {
        $cambios = [];

        if ($antes['usuario'] !== $despues['usuario']) {
            $cambios[] = "usuario {$antes['usuario']} → {$despues['usuario']}";
        }

        if ($antes['rol'] !== $despues['rol']) {
            $cambios[] = "rol {$antes['rol']} → {$despues['rol']}";
        }

        if ((bool) $antes['activo'] !== $despues['activo']) {
            $cambios[] = $despues['activo'] ? 'activado' : 'desactivado';
        }

        $permisosAntes = Cuenta::valoresDePermisos($antes['permisos']);

        if (array_diff($permisosAntes, $despues['permisos']) !== [] || array_diff($despues['permisos'], $permisosAntes) !== []) {
            $cambios[] = 'permisos: ' . self::listarPermisos($despues['permisos']);
        }

        if ($despues['contrasena'] !== '') {
            $cambios[] = 'contraseña restablecida';
        }

        return "Usuario {$antes['usuario']} actualizado" . ($cambios === [] ? '' : ': ' . implode(', ', $cambios));
    }
}
