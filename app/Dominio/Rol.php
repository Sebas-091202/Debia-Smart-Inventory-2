<?php

declare(strict_types=1);

namespace App\Dominio;

/**
 * Roles de usuario (coinciden con la restricción CHECK de usuarios.rol).
 */
enum Rol: string
{
    case Admin = 'ADMIN';
    case Usuario = 'USUARIO';

    public function esAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function paginaInicio(): string
    {
        return $this->esAdmin() ? 'index_Admin.php' : 'index_Usuario.php';
    }

    /** Las vistas de solo lectura del usuario llevan el sufijo "_usuario". */
    public function vista(string $nombreBase): string
    {
        return $nombreBase . ($this->esAdmin() ? '' : '_usuario') . '.php';
    }

    /**
     * Opciones del menú lateral y de la pantalla de inicio.
     *
     * @return array<int, array{archivo: string, icono: string, texto: string}>
     */
    public function menu(): array
    {
        $opcion = static fn (string $archivo, string $icono, string $texto) => compact('archivo', 'icono', 'texto');

        $soloAdmin = [
            $opcion('agregar_equipos.php', 'bx-plus-circle', 'Agregar Equipo'),
            $opcion('editar_equipos.php', 'bx-edit-alt', 'Editar Equipo'),
        ];

        return [
            $opcion($this->vista('ver_equipos'), 'bx-list-ul', 'Ver Equipos'),
            ...($this->esAdmin() ? $soloAdmin : []),
            $opcion($this->vista('ver_correctivos'), 'bx-check-square', 'Ver Correctivos'),
            $opcion($this->vista('ver_preventivos'), 'bx-calendar', 'Ver Preventivos'),
            $opcion($this->vista('indicadores_mantenimiento'), 'bx-bar-chart', 'Indicadores de Mantenimiento'),
            $opcion($this->vista('hoja_vida_equipos'), 'bx-file', 'Hoja de Vida General'),
            ...($this->esAdmin() ? [$opcion('usuarios.php', 'bx-group', 'Usuarios')] : []),
            $opcion('cambiar_contrasena.php', 'bx-key', 'Cambiar Contraseña'),
        ];
    }
}
