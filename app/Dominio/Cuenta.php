<?php

declare(strict_types=1);

namespace App\Dominio;

use App\Config;

/**
 * Lo que una cuenta puede hacer: su rol, sus permisos y si es la cuenta
 * principal (la del correo App\Config::CORREO_CUENTA_PRINCIPAL).
 *
 *   - El rol decide qué vistas ve: el administrador agrega y elimina
 *     equipos y registra mantenimientos; el usuario solo consulta.
 *   - Los permisos habilitan acciones puntuales sobre ese rol.
 *   - La cuenta principal tiene todos los permisos y es la única que
 *     asigna permisos, cambia nombres de usuario y edita su propia cuenta.
 */
final class Cuenta
{
    /** @param array $datos Fila de usuarios con id, usuario, correo, rol y permisos. */
    public function __construct(private readonly array $datos)
    {
    }

    public static function esCorreoPrincipal(?string $correo): bool
    {
        return $correo !== null && strcasecmp(trim($correo), Config::CORREO_CUENTA_PRINCIPAL) === 0;
    }

    /**
     * Permisos guardados en la base de datos ("A,B" desde la consulta) o
     * recibidos del formulario (lista).
     *
     * @return string[]
     */
    public static function valoresDePermisos(mixed $permisos): array
    {
        if (is_array($permisos)) {
            return array_values(array_filter($permisos, 'is_string'));
        }

        return array_values(array_filter(explode(',', (string) $permisos)));
    }

    public function id(): int
    {
        return (int) $this->datos['id'];
    }

    public function usuario(): string
    {
        return $this->datos['usuario'];
    }

    public function rol(): Rol
    {
        return Rol::from($this->datos['rol']);
    }

    public function esPrincipal(): bool
    {
        return self::esCorreoPrincipal($this->datos['correo'] ?? null);
    }

    /** @return Permiso[] */
    public function permisos(): array
    {
        if ($this->esPrincipal()) {
            return Permiso::cases();
        }

        return Permiso::desdeValores(self::valoresDePermisos($this->datos['permisos'] ?? ''));
    }

    public function puede(Permiso ...$permisos): bool
    {
        return array_intersect(Permiso::valores($permisos), Permiso::valores($this->permisos())) !== [];
    }

    /**
     * Nadie gestiona una cuenta con más privilegios que la suya: así un
     * permiso delegado no sirve para escalar (p. ej. restablecer la
     * contraseña de un administrador y entrar con ella).
     */
    public function puedeGestionar(self $objetivo): bool
    {
        if ($this->esPrincipal()) {
            return true;
        }

        if ($objetivo->esPrincipal() || !$this->puede(Permiso::EditarUsuario)) {
            return false;
        }

        $rolNoSuperior = !$objetivo->rol()->esAdmin() || $this->rol()->esAdmin();
        $permisosIncluidos = array_diff(Permiso::valores($objetivo->permisos()), Permiso::valores($this->permisos())) === [];

        return $rolNoSuperior && $permisosIncluidos;
    }

    /** @return Rol[] */
    public function rolesAsignables(): array
    {
        return $this->esPrincipal() || $this->rol()->esAdmin() ? Rol::cases() : [Rol::Usuario];
    }

    /**
     * Opciones del menú lateral y de la pantalla de inicio.
     *
     * @return array<int, array{archivo: string, icono: string, texto: string}>
     */
    public function menu(): array
    {
        $rol = $this->rol();
        $opcion = static fn (string $archivo, string $icono, string $texto) => compact('archivo', 'icono', 'texto');

        return [
            $opcion($rol->vista('ver_equipos'), 'bx-list-ul', 'Ver Equipos'),
            ...($rol->esAdmin() ? [$opcion('agregar_equipos.php', 'bx-plus-circle', 'Agregar Equipo')] : []),
            ...($this->puede(Permiso::EditarEquipo) ? [$opcion('editar_equipos.php', 'bx-edit-alt', 'Editar Equipo')] : []),
            $opcion($rol->vista('ver_correctivos'), 'bx-check-square', 'Ver Correctivos'),
            $opcion($rol->vista('ver_preventivos'), 'bx-calendar', 'Ver Preventivos'),
            $opcion($rol->vista('indicadores_mantenimiento'), 'bx-bar-chart', 'Indicadores de Mantenimiento'),
            $opcion($rol->vista('hoja_vida_equipos'), 'bx-file', 'Hoja de Vida General'),
            ...($this->puede(Permiso::CrearUsuario, Permiso::EditarUsuario) ? [$opcion('usuarios.php', 'bx-group', 'Usuarios')] : []),
            ...($this->puede(Permiso::CambiarContrasena) ? [$opcion('cambiar_contrasena.php', 'bx-key', 'Cambiar Contraseña')] : []),
        ];
    }
}
