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

    public function etiqueta(): string
    {
        return $this->esAdmin() ? 'Administrador' : 'Usuario';
    }
}
