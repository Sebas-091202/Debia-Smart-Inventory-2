<?php

declare(strict_types=1);

namespace App\Paginas;

use App\Core\Auth;
use App\Core\Paginador;
use App\Core\Peticion;
use App\Core\Vista;
use App\Dominio\FiltrosEquipo;
use App\Dominio\Permiso;
use App\Dominio\Rol;
use App\Repositorios\EquipoRepositorio;

/**
 * Inventario de equipos con filtros y paginación.
 * Con el permiso "Editar equipo" se ve la acción Editar; el
 * administrador ve además Eliminar.
 */
final class ListadoEquipos
{
    private const POR_PAGINA = 10;

    public static function mostrar(Rol $rol): void
    {
        $filtros = FiltrosEquipo::desdeConsulta();
        $equipos = new EquipoRepositorio();
        $paginador = new Paginador($equipos->contar($filtros), self::POR_PAGINA, Peticion::consultaEntero('pagina'));

        Vista::mostrar('paginas/equipos_listado', [
            'rol'             => $rol,
            'puedeEditar'     => Auth::cuenta()->puede(Permiso::EditarEquipo),
            'filtros'         => $filtros,
            'paginador'       => $paginador,
            'equipos'         => $equipos->listar($filtros, self::POR_PAGINA, $paginador->desplazamiento()),
            'identificadores' => $equipos->identificadores($filtros),
        ]);
    }
}
