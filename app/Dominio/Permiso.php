<?php

declare(strict_types=1);

namespace App\Dominio;

/**
 * Permisos que la cuenta principal asigna a cada usuario (coinciden con
 * la restricción CHECK de usuarios.permisos).
 */
enum Permiso: string
{
    case EditarEquipo = 'EDITAR_EQUIPO';
    case CrearUsuario = 'CREAR_USUARIO';
    case EditarUsuario = 'EDITAR_USUARIO';
    case CambiarContrasena = 'CAMBIAR_CONTRASENA';
    case VerIniciosSesion = 'VER_INICIOS_SESION';
    case VerBitacora = 'VER_BITACORA';

    /** Permisos de una cuenta nueva cuando no los elige la cuenta principal. */
    public const PREDETERMINADOS = [self::CambiarContrasena];

    public function etiqueta(): string
    {
        return match ($this) {
            self::EditarEquipo      => 'Editar equipo',
            self::CrearUsuario      => 'Crear nuevo usuario',
            self::EditarUsuario     => 'Editar usuario',
            self::CambiarContrasena => 'Cambio de contraseña',
            self::VerIniciosSesion  => 'Ver inicios de sesión',
            self::VerBitacora       => 'Ver bitácora de cambios',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::EditarEquipo      => 'Modificar los datos de los equipos del inventario.',
            self::CrearUsuario      => 'Crear cuentas nuevas (solo con rol y permisos iguales o menores a los suyos).',
            self::EditarUsuario     => 'Editar datos, estado y contraseña de cuentas con privilegios iguales o menores.',
            self::CambiarContrasena => 'Cambiar su propia contraseña desde el menú.',
            self::VerIniciosSesion  => 'Consultar quién entró al sistema, cuándo, desde qué IP y los intentos fallidos.',
            self::VerBitacora       => 'Consultar quién creó, editó o eliminó información y en qué fecha y hora, incluidos los administradores.',
        };
    }

    /**
     * Convierte valores de texto (de la base de datos o del formulario)
     * en permisos, descartando los desconocidos y los repetidos.
     *
     * @param  string[]  $valores
     * @return Permiso[]
     */
    public static function desdeValores(array $valores): array
    {
        return array_values(array_filter(
            array_map(static fn (string $valor) => self::tryFrom($valor), array_unique($valores))
        ));
    }

    /**
     * @param  Permiso[] $permisos
     * @return string[]
     */
    public static function valores(array $permisos): array
    {
        return array_map(static fn (Permiso $permiso) => $permiso->value, $permisos);
    }
}
