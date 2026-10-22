<?php

declare(strict_types=1);

namespace App\Paginas;

use App\Core\Paginador;
use App\Core\Peticion;
use App\Core\Vista;
use App\Dominio\FiltrosIniciosSesion;
use App\Dominio\Rol;
use App\Repositorios\InicioSesionRepositorio;
use App\Repositorios\UsuarioRepositorio;

/**
 * Historial de inicios de sesión (permiso "Ver inicios de sesión").
 */
final class IniciosSesion
{
    private const POR_PAGINA = 20;

    public static function mostrar(Rol $rol): void
    {
        $filtros = FiltrosIniciosSesion::desdeConsulta();
        $inicios = new InicioSesionRepositorio();
        $paginador = new Paginador($inicios->contar($filtros), self::POR_PAGINA, Peticion::consultaEntero('pagina'));

        Vista::mostrar('paginas/inicios_sesion', [
            'rol'       => $rol,
            'filtros'   => $filtros,
            'paginador' => $paginador,
            'inicios'   => $inicios->listar($filtros, self::POR_PAGINA, $paginador->desplazamiento()),
            'usuarios'  => (new UsuarioRepositorio())->opciones(),
        ]);
    }
}
