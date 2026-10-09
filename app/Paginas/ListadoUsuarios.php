<?php

declare(strict_types=1);

namespace App\Paginas;

use App\Core\Auth;
use App\Core\Paginador;
use App\Core\Peticion;
use App\Core\Vista;
use App\Dominio\Rol;
use App\Repositorios\UsuarioRepositorio;

/**
 * Usuarios del sistema, paginados. Cada fila muestra "Editar" solo si
 * la cuenta actual puede gestionarla (ver Cuenta::puedeGestionar).
 */
final class ListadoUsuarios
{
    private const POR_PAGINA = 10;

    public static function mostrar(Rol $rol): void
    {
        $usuarios = new UsuarioRepositorio();
        $paginador = new Paginador($usuarios->contar(), self::POR_PAGINA, Peticion::consultaEntero('pagina'));

        Vista::mostrar('paginas/usuarios_listado', [
            'rol'       => $rol,
            'cuenta'    => Auth::cuenta(),
            'paginador' => $paginador,
            'usuarios'  => $usuarios->listar(self::POR_PAGINA, $paginador->desplazamiento()),
        ]);
    }
}
